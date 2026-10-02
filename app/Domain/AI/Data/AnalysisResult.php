<?php

namespace App\Domain\AI\Data;

/**
 * Structured output of the intake engine. Shape intentionally mirrors the product spec:
 * category, subcategory, urgency, confidence, summary, missing_information, suggested_actions,
 * recommended_contents, required_expertises.
 */
final class AnalysisResult
{
    /**
     * @param  array<int, string>  $facts
     * @param  array<int, string>  $missingInformation
     * @param  array<int, string>  $suggestedActions
     * @param  array<int, array{id:int, slug:string, score:float}>  $recommendedContents
     * @param  array<string, mixed>  $guidance
     * @param  array<int, string>  $safetyFlags
     */
    public function __construct(
        public readonly Classification $classification,
        public readonly string $summary,
        public readonly array $facts,
        public readonly array $missingInformation,
        public readonly array $suggestedActions,
        public readonly array $recommendedContents,
        public readonly array $guidance,
        public readonly array $safetyFlags,
        public readonly bool $isSensitive,
        public readonly bool $needsExpert,
        public readonly string $provider,
        public readonly string $model,
    ) {}

    public function toArray(): array
    {
        return [
            'category' => $this->classification->category?->slug,
            'subcategory' => $this->classification->subcategory?->slug,
            'urgency' => $this->classification->urgency->value,
            'confidence' => round($this->classification->confidence, 3),
            'summary' => $this->summary,
            'facts' => $this->facts,
            'missing_information' => $this->missingInformation,
            'suggested_actions' => $this->suggestedActions,
            'recommended_contents' => $this->recommendedContents,
            'required_expertises' => $this->classification->requiredExpertises,
            'is_sensitive' => $this->isSensitive,
            'safety_flags' => $this->safetyFlags,
            'needs_expert' => $this->needsExpert,
        ];
    }
}
