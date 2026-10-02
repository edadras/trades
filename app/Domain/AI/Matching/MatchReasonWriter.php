<?php

namespace App\Domain\AI\Matching;

/**
 * Turns a match-score breakdown into human readable, bilingual reasons such as
 * "Experience optimising energy use in manufacturing · Persian speaking · 14 similar cases · Available this week".
 */
class MatchReasonWriter
{
    /**
     * @param  array<string, mixed>  $facts
     * @return array<int, array{fa: string, en: string}>
     */
    public function write(array $facts): array
    {
        $reasons = [];
        $both = fn (string $key, array $replace = []) => ['fa' => __("matching.reasons.{$key}", $replace, 'fa'), 'en' => __("matching.reasons.{$key}", $replace, 'en')];

        if (! empty($facts['skill'])) {
            $reasons[] = [
                'fa' => __('matching.reasons.skill', ['skill' => $facts['skill']['fa'], 'years' => $facts['skill_years']], 'fa'),
                'en' => __('matching.reasons.skill', ['skill' => $facts['skill']['en'], 'years' => $facts['skill_years']], 'en'),
            ];
        }
        if (! empty($facts['industry'])) {
            $reasons[] = [
                'fa' => __('matching.reasons.industry', ['industry' => __('options.industries.'.$facts['industry'], [], 'fa')], 'fa'),
                'en' => __('matching.reasons.industry', ['industry' => __('options.industries.'.$facts['industry'], [], 'en')], 'en'),
            ];
        }
        if (! empty($facts['language'])) {
            $reasons[] = $both('language_'.$facts['language']);
        }
        if (! empty($facts['similar_cases'])) {
            $reasons[] = $both('similar_cases', ['count' => $facts['similar_cases']]);
        }
        if (! empty($facts['success_rate'])) {
            $reasons[] = $both('success_rate', ['rate' => $facts['success_rate']]);
        }
        if (! empty($facts['country'])) {
            $reasons[] = $both('country');
        }
        if (! empty($facts['available'])) {
            $reasons[] = $both('available');
        }

        return $reasons;
    }
}
