<?php

namespace App\Domain\AI\Summarization;

use App\Domain\AI\Support\TextNormalizer;

class CaseSummarizer
{
    /** A short neutral summary built from the first sentences of the narrative. */
    public function summarize(string $text, int $limit = 320): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        $sentences = preg_split('/(?<=[.!?؟。])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [$text];
        $summary = '';
        foreach ($sentences as $sentence) {
            if (mb_strlen($summary.' '.$sentence) > $limit && $summary !== '') {
                break;
            }
            $summary = trim($summary.' '.$sentence);
        }

        return mb_strlen($summary) > $limit ? mb_substr($summary, 0, $limit - 1).'…' : $summary;
    }

    /**
     * Extracts measurable facts (percentages, amounts, durations) and the intake answers.
     *
     * @param  array<int, array{question:string, answer:?string}>  $answers
     * @return array<int, string>
     */
    public function facts(string $text, array $answers = []): array
    {
        $facts = [];
        $normalized = TextNormalizer::normalize($text);
        $patterns = [
            '/\d+(?:[.,]\d+)?\s*%/u',
            '/\d+(?:[.,]\d+)?\s*(?:درصد|percent)/u',
            '/\d+(?:[.,]\d+)?\s*(?:میلیون|میلیارد|هزار|million|billion|thousand|تومان|ریال|dollars?|usd|eur|€|\$)/u',
            '/(?:\d+|یک|دو|سه|چهار|پنج|شش|هفت|هشت|نه|ده)\s*(?:روز|هفته|ماه|سال|days?|weeks?|months?|years?)/u',
        ];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $normalized, $m)) {
                foreach ($m[0] as $match) {
                    $facts[] = trim($match);
                }
            }
        }
        foreach ($answers as $a) {
            if (filled($a['answer'] ?? null)) {
                $facts[] = trim($a['question']).' → '.trim((string) $a['answer']);
            }
        }

        return array_values(array_unique($facts));
    }
}
