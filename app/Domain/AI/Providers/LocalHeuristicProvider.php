<?php

namespace App\Domain\AI\Providers;

/**
 * Offline provider. It does not generate text with a language model: each AI capability
 * falls back to its deterministic engine (keyword taxonomy, knowledge-base composition)
 * when this provider is active. Used in development, tests, and when no API key is set.
 */
class LocalHeuristicProvider implements AIProviderInterface
{
    public function name(): string
    {
        return 'local';
    }

    public function model(): string
    {
        return config('ai.providers.local.model', 'heuristic-v1');
    }

    public function supportsGeneration(): bool
    {
        return false;
    }

    public function generateJson(string $system, array $messages, float $temperature = 0.2): array
    {
        throw new AIProviderException('The local provider does not support generation.');
    }

    public function transcribe(string $absolutePath, ?string $language = null): ?string
    {
        return null;
    }
}
