<?php

namespace App\Domain\Matching;

use App\Domain\AI\Matching\MatchReasonWriter;
use App\Domain\Cases\Enums\OutcomeType;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Matching\Enums\MatchStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Scores verified experts for a case on:
 * expertise (35) + industry (15) + language (15) + country (10) + availability (10) + previous results (10) + workload (5).
 * Returns the best candidates with a breakdown and bilingual reasons explaining the match.
 */
class MatchingEngine
{
    public const WEIGHTS = ['expertise' => 35, 'industry' => 15, 'language' => 15, 'country' => 10, 'availability' => 10, 'results' => 10, 'workload' => 5];

    public function __construct(private readonly MatchReasonWriter $reasons) {}

    /** @return Collection<int, array{expert: ExpertProfile, score: float, breakdown: array<string, float>, reasons: array}> */
    public function rank(SupportCase $case, ?int $limit = null): Collection
    {
        $limit ??= config('platform.max_matches_per_case');
        $case->loadMissing(['business', 'category', 'subcategory']);
        if (! $case->category_id) {
            return collect();
        }

        // Experts the business rejected, who declined, or who already left / were removed from this case.
        // Proposals withdrawn only because another expert was engaged stay eligible for re-matching.
        $excluded = $case->matches()->whereIn('status', [MatchStatus::RejectedByBusiness->value, MatchStatus::ExpertDeclined->value])->pluck('expert_profile_id')
            ->merge($case->caseExperts()->whereIn('status', ['left', 'removed'])->pluck('expert_profile_id'))
            ->merge($case->caseExperts()->where('status', 'active')->pluck('expert_profile_id'));
        $categoryIds = array_filter([$case->category_id, $case->subcategory_id]);

        $experts = ExpertProfile::query()->verified()->where('is_available', true)
            ->whereNotIn('id', $excluded)
            ->whereHas('skills', fn ($q) => $q->whereIn('case_category_id', $categoryIds))
            ->where('user_id', '!=', $case->business->owner_id)
            ->when(! $case->consents('share_with_foreign_experts'), fn ($q) => $q->where('country', $case->business->country))
            ->with(['user', 'skills', 'languages', 'availability', 'categories'])
            ->get();

        $history = $this->history($experts->pluck('id')->all(), $case->category_id);

        return $experts->map(fn (ExpertProfile $expert) => $this->score($expert, $case, $history[$expert->id] ?? ['total' => 0, 'success' => 0]))
            ->filter(fn ($r) => $r !== null)
            ->sortByDesc('score')->take($limit)->values();
    }

    /** @return array{expert: ExpertProfile, score: float, breakdown: array<string, float>, reasons: array}|null */
    public function score(ExpertProfile $expert, SupportCase $case, array $history): ?array
    {
        $active = $expert->activeCaseCount();
        if ($active >= $expert->max_active_cases) {
            return null;
        }

        $sub = $expert->skills->firstWhere('case_category_id', $case->subcategory_id);
        $root = $expert->skills->firstWhere('case_category_id', $case->category_id);
        $skill = $sub ?? $root;
        $levelFactor = 0.6 + 0.08 * min(5, max(1, (int) $skill->level));
        $expertise = self::WEIGHTS['expertise'] * $levelFactor * ($sub ? 1 : 0.8);

        $industry = $case->business->industry && in_array($case->business->industry, $expert->industries ?? [], true) ? self::WEIGHTS['industry'] : 0;
        $language = $case->business->preferred_language ?: $case->locale;
        $speaks = $expert->languages->pluck('language')->contains($language);
        $languageScore = $speaks ? self::WEIGHTS['language'] : 0;

        $country = $case->business->country;
        $countryScore = $expert->country === $country || in_array($country, $expert->serves_countries ?? [], true) ? self::WEIGHTS['country'] : 0;

        $availability = $expert->is_available ? ($expert->availability->isNotEmpty() ? self::WEIGHTS['availability'] : self::WEIGHTS['availability'] * 0.6) : 0;

        $successRate = $history['total'] > 0 ? $history['success'] / $history['total'] : null;
        $results = $successRate === null ? self::WEIGHTS['results'] * 0.4 : self::WEIGHTS['results'] * (0.7 * $successRate + 0.3 * min(1, $history['total'] / 10));

        $workload = self::WEIGHTS['workload'] * (1 - $active / max(1, $expert->max_active_cases));

        $breakdown = array_map(fn ($v) => round($v, 1), [
            'expertise' => $expertise, 'industry' => $industry, 'language' => $languageScore, 'country' => $countryScore,
            'availability' => $availability, 'results' => $results, 'workload' => $workload,
        ]);

        $skillCategory = $sub ? $case->subcategory : $case->category;
        $reasons = $this->reasons->write([
            'skill' => ['fa' => $skillCategory?->translate('name', 'fa'), 'en' => $skillCategory?->translate('name', 'en')],
            'skill_years' => max((int) $skill->years, (int) $expert->years_experience),
            'industry' => $industry ? $case->business->industry : null,
            'language' => $speaks ? $language : null,
            'similar_cases' => $history['total'],
            'success_rate' => $successRate !== null && $history['total'] >= 3 ? (int) round($successRate * 100) : null,
            'country' => $countryScore > 0,
            'available' => $availability >= self::WEIGHTS['availability'],
        ]);

        return ['expert' => $expert, 'score' => round(array_sum($breakdown), 1), 'breakdown' => $breakdown, 'reasons' => $reasons];
    }

    /** @return array<int, array{total: int, success: int}> past outcomes per expert in this category */
    private function history(array $expertIds, int $categoryId): array
    {
        if (! $expertIds) {
            return [];
        }

        $rows = DB::table('case_experts')
            ->join('cases', 'cases.id', '=', 'case_experts.case_id')
            ->leftJoin('case_outcomes', fn ($j) => $j->on('case_outcomes.case_id', '=', 'cases.id')->whereNull('case_outcomes.superseded_at'))
            ->whereIn('case_experts.expert_profile_id', $expertIds)
            ->where('cases.category_id', $categoryId)
            ->whereNotNull('case_outcomes.id')
            ->groupBy('case_experts.expert_profile_id')
            ->selectRaw('case_experts.expert_profile_id as id, count(*) as total, sum(case when case_outcomes.outcome in (?, ?, ?) then 1 else 0 end) as success', [
                OutcomeType::Resolved->value, OutcomeType::PartiallyResolved->value, OutcomeType::EffectiveActionStarted->value,
            ])
            ->get();

        return $rows->mapWithKeys(fn ($r) => [$r->id => ['total' => (int) $r->total, 'success' => (int) $r->success]])->all();
    }
}
