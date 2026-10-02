<?php

namespace App\Domain\AI\Knowledge;

use App\Domain\AI\AIManager;
use App\Domain\AI\Data\Classification;
use App\Domain\AI\Providers\AIProviderException;
use App\Domain\AI\Safety\PiiRedactor;
use App\Domain\Cases\Enums\Urgency;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Builds the "initial guidance" shown to the business strictly from retrieved, approved knowledge:
 * situation summary, possible causes, suggested actions, required documents, related learning,
 * warnings and whether an expert is needed. Every item carries the ids of the sources it came from.
 */
class GuidanceComposer
{
    public function __construct(private readonly AIManager $ai, private readonly PiiRedactor $redactor) {}

    /**
     * @param  Collection<int, array{article: KnowledgeArticle, score: float}>  $retrieved
     * @param  array<int, string>  $safetyFlags
     * @return array<string, mixed>
     */
    public function compose(string $text, string $summary, Classification $classification, Collection $retrieved, array $safetyFlags, string $locale): array
    {
        if ($this->ai->usesLanguageModel() && $retrieved->isNotEmpty()) {
            try {
                return $this->composeWithModel($text, $summary, $classification, $retrieved, $safetyFlags, $locale);
            } catch (AIProviderException $e) {
                Log::warning('AI guidance fell back to knowledge composition', ['error' => $e->getMessage()]);
            }
        }

        return $this->composeFromKnowledge($summary, $classification, $retrieved, $safetyFlags, $locale);
    }

    public function composeFromKnowledge(string $summary, Classification $classification, Collection $retrieved, array $safetyFlags, string $locale): array
    {
        $sections = ['causes' => [], 'actions' => [], 'documents' => [], 'warnings' => []];
        foreach ($retrieved as $item) {
            $t = $item['article']->translation($locale);
            foreach ($sections as $key => $_) {
                foreach (($t?->checklist[$key] ?? []) as $line) {
                    if (count($sections[$key]) < 5 && ! in_array($line, array_column($sections[$key], 'text'), true)) {
                        $sections[$key][] = ['text' => $line, 'sources' => [$item['article']->id]];
                    }
                }
            }
        }
        foreach ($safetyFlags as $flag) {
            $sections['warnings'][] = ['text' => __("ai.safety.{$flag}", [], $locale), 'sources' => []];
        }

        $topScore = (float) ($retrieved->first()['score'] ?? 0);

        return [
            'situation' => $summary,
            'possible_causes' => $sections['causes'],
            'suggested_actions' => $sections['actions'],
            'required_documents' => $sections['documents'],
            'warnings' => $sections['warnings'],
            'related_content_ids' => $retrieved->pluck('article.id')->all(),
            'needs_expert' => $this->needsExpert($classification, $safetyFlags, $topScore),
            'grounded' => $retrieved->isNotEmpty(),
            'generated_by' => 'knowledge_base',
        ];
    }

    private function needsExpert(Classification $classification, array $safetyFlags, float $topScore): bool
    {
        if ($safetyFlags || $classification->category?->is_sensitive || $classification->urgency->weight() >= Urgency::High->weight()) {
            return true;
        }

        return ! ($classification->urgency === Urgency::Low && $topScore >= 0.6);
    }

    private function composeWithModel(string $text, string $summary, Classification $classification, Collection $retrieved, array $safetyFlags, string $locale): array
    {
        $language = $locale === 'en' ? 'English' : 'Persian (Farsi)';
        $knowledge = $retrieved->map(function ($item) use ($locale) {
            $t = $item['article']->translation($locale);

            return ['id' => $item['article']->id, 'title' => $t?->title, 'summary' => $t?->summary, 'checklist' => $t?->checklist, 'body' => mb_substr(strip_tags((string) $t?->body), 0, 2500)];
        })->values()->all();

        $system = "You write initial guidance for a business problem in {$language}. Use ONLY the APPROVED_KNOWLEDGE below; "
            .'do not use outside knowledge, do not invent laws, figures or sources. Every item must cite the ids it relies on. '
            .'This is a suggestion, not a final decision. Return JSON: {"situation": string, "possible_causes": [{"text","sources":[id]}], '
            .'"suggested_actions": [{"text","sources":[id]}], "required_documents": [{"text","sources":[id]}], "warnings": [{"text","sources":[id]}], "needs_expert": bool}. '
            .'APPROVED_KNOWLEDGE: '.json_encode($knowledge, JSON_UNESCAPED_UNICODE);

        $json = $this->ai->provider()->generateJson($system, [['role' => 'user', 'content' => $this->redactor->redact("Summary: {$summary}\n\nDetails: {$text}")]]);

        $allowed = $retrieved->pluck('article.id')->all();
        $clean = fn ($items) => collect(is_array($items) ? $items : [])
            ->map(fn ($i) => ['text' => (string) ($i['text'] ?? ''), 'sources' => array_values(array_intersect((array) ($i['sources'] ?? []), $allowed))])
            ->filter(fn ($i) => $i['text'] !== '' && $i['sources'] !== [])
            ->take(6)->values()->all();

        $fallback = $this->composeFromKnowledge($summary, $classification, $retrieved, $safetyFlags, $locale);
        $warnings = array_merge($clean($json['warnings'] ?? []), array_filter($fallback['warnings'], fn ($w) => $w['sources'] === []));

        return [
            'situation' => (string) ($json['situation'] ?? $summary),
            'possible_causes' => $clean($json['possible_causes'] ?? []) ?: $fallback['possible_causes'],
            'suggested_actions' => $clean($json['suggested_actions'] ?? []) ?: $fallback['suggested_actions'],
            'required_documents' => $clean($json['required_documents'] ?? []) ?: $fallback['required_documents'],
            'warnings' => $warnings,
            'related_content_ids' => $allowed,
            'needs_expert' => ($json['needs_expert'] ?? true) || $fallback['needs_expert'],
            'grounded' => true,
            'generated_by' => 'model',
        ];
    }
}
