<?php

namespace App\Domain\AI;

use App\Domain\AI\Providers\AIProviderInterface;
use App\Domain\AI\Providers\LocalHeuristicProvider;
use App\Domain\AI\Providers\OpenAICompatibleProvider;

class AIManager
{
    private ?AIProviderInterface $resolved = null;

    public function provider(): AIProviderInterface
    {
        if ($this->resolved) {
            return $this->resolved;
        }

        $name = config('ai.default');
        $provider = match ($name) {
            'openai' => new OpenAICompatibleProvider(config('ai.providers.openai')),
            default => new LocalHeuristicProvider,
        };

        return $this->resolved = $provider->supportsGeneration() ? $provider : new LocalHeuristicProvider;
    }

    public function setProvider(AIProviderInterface $provider): void
    {
        $this->resolved = $provider;
    }

    public function usesLanguageModel(): bool
    {
        return $this->provider()->supportsGeneration();
    }
}
