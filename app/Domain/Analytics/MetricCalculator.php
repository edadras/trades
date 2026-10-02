<?php

namespace App\Domain\Analytics;

use App\Domain\AI\Models\AiHumanReview;
use App\Domain\Business\Models\Business;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Enums\OutcomeType;
use App\Domain\Cases\Models\CaseOutcome;
use App\Domain\Cases\Models\SatisfactionSurvey;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Matching\Enums\MatchStatus;
use App\Domain\Matching\Models\ExpertMatch;
use InvalidArgumentException;

/**
 * Every metric a KPI can be bound to. KPIs are data (kpis table); metrics are the measurable sources.
 */
class MetricCalculator
{
    /** @return array<string, string> metric key => unit */
    public static function metrics(): array
    {
        return [
            'active_businesses' => 'count',
            'verified_supporters' => 'count',
            'verified_supporters_domestic' => 'count',
            'verified_supporters_abroad' => 'count',
            'initial_review_sla_rate' => 'percent',
            'dissatisfaction_rate' => 'percent',
            'outcome_confirmation_rate' => 'percent',
            'real_cases' => 'count',
            'initial_review_hours' => 'hours',
            'ai_agreement_rate' => 'percent',
            'clear_next_action_rate' => 'percent',
            'match_acceptance_rate' => 'percent',
            'satisfaction_avg' => 'score',
            'resolution_rate' => 'percent',
            'effective_action_rate' => 'percent',
            'expert_response_hours' => 'hours',
        ];
    }

    public function value(string $metric): ?float
    {
        return match ($metric) {
            'active_businesses' => (float) Business::whereNotNull('onboarding_completed_at')->count(),
            'verified_supporters' => (float) ExpertProfile::verified()->count(),
            'verified_supporters_domestic' => (float) ExpertProfile::verified()->where('country', 'IR')->count(),
            'verified_supporters_abroad' => (float) ExpertProfile::verified()->where('country', '!=', 'IR')->count(),
            'initial_review_sla_rate' => $this->initialReviewSlaRate(),
            'dissatisfaction_rate' => ($n = SatisfactionSurvey::count()) ? round(SatisfactionSurvey::where('rating', '<=', 3)->count() / $n * 100, 1) : null,
            'outcome_confirmation_rate' => ($n = CaseOutcome::whereNull('superseded_at')->count()) ? round(CaseOutcome::whereNull('superseded_at')->where('confirmation_status', 'confirmed')->count() / $n * 100, 1) : null,
            'real_cases' => (float) SupportCase::real()->count(),
            'initial_review_hours' => $this->initialReviewHours(),
            'ai_agreement_rate' => $this->aiAgreementRate(),
            'clear_next_action_rate' => $this->clearNextActionRate(),
            'match_acceptance_rate' => $this->matchAcceptanceRate(),
            'satisfaction_avg' => ($avg = SatisfactionSurvey::avg('rating')) !== null ? round((float) $avg, 2) : null,
            'resolution_rate' => $this->outcomeRate([OutcomeType::Resolved, OutcomeType::PartiallyResolved]),
            'effective_action_rate' => $this->outcomeRate([OutcomeType::Resolved, OutcomeType::PartiallyResolved, OutcomeType::EffectiveActionStarted]),
            'expert_response_hours' => $this->expertResponseHours(),
            default => throw new InvalidArgumentException("Unknown metric [{$metric}]"),
        };
    }

    /** Average hours from submission to the first decision (human review or confident AI routing). */
    public function initialReviewHours(): ?float
    {
        $cases = SupportCase::real()->whereNotNull('submitted_at')
            ->where(fn ($q) => $q->whereNotNull('first_reviewed_at')->orWhereNotNull('ready_at'))
            ->get(['submitted_at', 'first_reviewed_at', 'ready_at']);
        if ($cases->isEmpty()) {
            return null;
        }

        return round($cases->avg(function ($c) {
            $reviewed = collect([$c->first_reviewed_at, $c->ready_at])->filter()->min();

            return max(0, $c->submitted_at->diffInMinutes($reviewed)) / 60;
        }), 1);
    }

    /**
     * Share of cases whose first decision came within the SLA (48h). Cases still undecided count as
     * breaches once the SLA has passed; cases still inside the window are not counted yet.
     */
    public function initialReviewSlaRate(): ?float
    {
        $sla = (int) config('platform.initial_review_sla_hours');
        $cases = SupportCase::real()->whereNotNull('submitted_at')->get(['submitted_at', 'first_reviewed_at', 'ready_at']);
        $counted = 0;
        $within = 0;
        foreach ($cases as $c) {
            $decided = collect([$c->first_reviewed_at, $c->ready_at])->filter()->min();
            if (! $decided && $c->submitted_at->diffInHours(now()) < $sla) {
                continue;
            }
            $counted++;
            if ($decided && $c->submitted_at->diffInMinutes($decided) <= $sla * 60) {
                $within++;
            }
        }

        return $counted ? round($within / $counted * 100, 1) : null;
    }

    public function aiAgreementRate(): ?float
    {
        $reviews = AiHumanReview::where('status', 'completed')->whereNotNull('category_agreed');
        $total = (clone $reviews)->count();

        return $total ? round((clone $reviews)->where('category_agreed', true)->count() / $total * 100, 1) : null;
    }

    public function clearNextActionRate(): ?float
    {
        $analysed = SupportCase::real()->whereNotNull('accepted_at');
        $total = (clone $analysed)->count();
        if (! $total) {
            return null;
        }
        $clear = (clone $analysed)->where(fn ($q) => $q->whereNotNull('next_action')
            ->orWhereHas('tasks', fn ($t) => $t->where('is_next_action', true))
            ->orWhereHas('outcome'))->count();

        return round($clear / $total * 100, 1);
    }

    public function matchAcceptanceRate(): ?float
    {
        $accepted = ExpertMatch::whereIn('status', [MatchStatus::Invited->value, MatchStatus::Active->value, MatchStatus::ExpertDeclined->value])->count();
        $rejected = ExpertMatch::where('status', MatchStatus::RejectedByBusiness->value)->count();
        $total = $accepted + $rejected;

        return $total ? round($accepted / $total * 100, 1) : null;
    }

    /** @param array<int, OutcomeType> $types */
    public function outcomeRate(array $types): ?float
    {
        $current = CaseOutcome::whereNull('superseded_at')->where('confirmation_status', 'confirmed');
        $total = (clone $current)->count();

        return $total ? round((clone $current)->whereIn('outcome', array_map(fn ($t) => $t->value, $types))->count() / $total * 100, 1) : null;
    }

    public function expertResponseHours(): ?float
    {
        $matches = ExpertMatch::whereNotNull('business_decided_at')->whereNotNull('expert_decided_at')->get(['business_decided_at', 'expert_decided_at']);

        return $matches->isEmpty() ? null : round($matches->avg(fn ($m) => $m->business_decided_at->diffInMinutes($m->expert_decided_at) / 60), 1);
    }

    /** Snapshot of case counts per status, used for the funnel. */
    public function funnel(): array
    {
        return [
            ['key' => 'submitted', 'value' => SupportCase::real()->count()],
            ['key' => 'analysed', 'value' => SupportCase::real()->whereHas('analyses')->count()],
            ['key' => 'ready', 'value' => SupportCase::real()->whereNotNull('ready_at')->count()],
            ['key' => 'matched', 'value' => SupportCase::real()->whereHas('matches')->count()],
            ['key' => 'accepted', 'value' => SupportCase::real()->whereNotNull('accepted_at')->count()],
            ['key' => 'resolved', 'value' => SupportCase::real()->whereNotNull('resolved_at')->count()],
            ['key' => 'closed', 'value' => SupportCase::where('status', CaseStatus::Closed->value)->count()],
        ];
    }
}
