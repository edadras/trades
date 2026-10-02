<?php

namespace App\Domain\Analytics;

use App\Domain\AI\Models\AiHumanReview;
use App\Domain\Business\Models\Business;
use App\Domain\Cases\Enums\OutcomeType;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Experts\Models\ExpertProfile;
use Illuminate\Support\Facades\DB;

/** Aggregations for the admin dashboard charts. */
class DashboardReport
{
    public function __construct(private readonly MetricCalculator $metrics) {}

    public function summary(): array
    {
        return [
            'businesses' => Business::whereNotNull('onboarding_completed_at')->count(),
            'cases' => SupportCase::real()->count(),
            'open_cases' => SupportCase::open()->count(),
            'experts' => ExpertProfile::verified()->count(),
            'pending_experts' => ExpertProfile::whereIn('verification_status', ['submitted', 'in_review'])->count(),
            'review_queue' => AiHumanReview::pending()->count(),
            'ai_accuracy' => $this->metrics->aiAgreementRate(),
            'avg_response_hours' => $this->metrics->initialReviewHours(),
            'match_acceptance' => $this->metrics->matchAcceptanceRate(),
            'resolution_rate' => $this->metrics->outcomeRate([OutcomeType::Resolved, OutcomeType::PartiallyResolved]),
            'satisfaction' => $this->metrics->value('satisfaction_avg'),
        ];
    }

    public function casesByCategory(): array
    {
        $counts = SupportCase::real()->whereNotNull('category_id')->selectRaw('category_id, count(*) as c')->groupBy('category_id')->pluck('c', 'category_id');
        $categories = CaseCategory::whereIn('id', $counts->keys())->get()->keyBy('id');

        return $counts->map(fn ($c, $id) => ['label' => $categories[$id]?->translate('name') ?? '—', 'value' => (int) $c])->sortByDesc('value')->values()->all();
    }

    public function casesByRegion(): array
    {
        return SupportCase::real()->join('businesses', 'businesses.id', '=', 'cases.business_id')
            ->selectRaw('coalesce(businesses.province, businesses.country) as region, count(*) as c')
            ->groupBy('region')->orderByDesc('c')->limit(10)->get()
            ->map(fn ($r) => ['key' => $r->region, 'label' => __('options.provinces.'.$r->region) !== 'options.provinces.'.$r->region ? __('options.provinces.'.$r->region) : __('options.countries.'.$r->region), 'value' => (int) $r->c])->all();
    }

    public function casesByIndustry(): array
    {
        return SupportCase::real()->join('businesses', 'businesses.id', '=', 'cases.business_id')
            ->whereNotNull('businesses.industry')
            ->selectRaw('businesses.industry as industry, count(*) as c')->groupBy('industry')->orderByDesc('c')->limit(10)->get()
            ->map(fn ($r) => ['key' => $r->industry, 'label' => __('options.industries.'.$r->industry), 'value' => (int) $r->c])->all();
    }

    /** AI vs human: completed reviews split into agreed / category changed / urgency changed. */
    public function aiVsHuman(): array
    {
        $reviews = AiHumanReview::where('status', 'completed')->get(['category_agreed', 'urgency_agreed', 'reviewed_at']);

        return [
            'agreed' => $reviews->where('category_agreed', true)->where('urgency_agreed', '!==', false)->count(),
            'category_changed' => $reviews->where('category_agreed', false)->count(),
            'urgency_changed' => $reviews->where('category_agreed', true)->where('urgency_agreed', false)->count(),
            'by_month' => $reviews->filter(fn ($r) => $r->reviewed_at)->groupBy(fn ($r) => $r->reviewed_at->format('Y-m'))
                ->map(fn ($g, $month) => ['month' => $month, 'rate' => round($g->where('category_agreed', true)->count() / max(1, $g->whereNotNull('category_agreed')->count()) * 100, 1)])
                ->sortKeys()->values()->all(),
        ];
    }

    public function funnel(): array
    {
        return $this->metrics->funnel();
    }

    public function expertPerformance(): array
    {
        $rows = DB::table('case_experts')
            ->join('expert_profiles', 'expert_profiles.id', '=', 'case_experts.expert_profile_id')
            ->join('users', 'users.id', '=', 'expert_profiles.user_id')
            ->leftJoin('case_outcomes', 'case_outcomes.case_id', '=', 'case_experts.case_id')
            ->leftJoin('satisfaction_surveys', 'satisfaction_surveys.case_id', '=', 'case_experts.case_id')
            ->groupBy('expert_profiles.id', 'users.name', 'expert_profiles.avg_response_minutes')
            ->selectRaw("expert_profiles.id, users.name, expert_profiles.avg_response_minutes,
                count(distinct case_experts.case_id) as cases,
                count(distinct case when case_outcomes.outcome in ('resolved','partially_resolved','effective_action_started') then case_outcomes.case_id end) as successful,
                avg(satisfaction_surveys.rating) as rating")
            ->orderByDesc('cases')->limit(10)->get();

        return $rows->map(fn ($r) => [
            'id' => $r->id, 'name' => $r->name, 'cases' => (int) $r->cases, 'successful' => (int) $r->successful,
            'rating' => $r->rating ? round((float) $r->rating, 1) : null, 'response_minutes' => $r->avg_response_minutes,
        ])->all();
    }

    public function casesOverTime(int $weeks = 12): array
    {
        $from = now()->startOfWeek()->subWeeks($weeks - 1);
        $cases = SupportCase::real()->where('submitted_at', '>=', $from)->get(['submitted_at', 'resolved_at']);
        $out = [];
        for ($i = 0; $i < $weeks; $i++) {
            $start = $from->copy()->addWeeks($i);
            $end = $start->copy()->endOfWeek();
            $out[] = [
                'label' => $start->format('m/d'),
                'submitted' => $cases->filter(fn ($c) => $c->submitted_at->between($start, $end))->count(),
                'resolved' => $cases->filter(fn ($c) => $c->resolved_at?->between($start, $end))->count(),
            ];
        }

        return $out;
    }
}
