<?php

namespace App\Domain\AI\Providers;

/**
 * Contract for any language-model backend. Business code never talks to a vendor SDK directly,
 * so the provider can be swapped (OpenAI, Azure, a self-hosted OpenAI-compatible server, ...).
 */
interface AIProviderInterface
{
    public function name(): string;

    public function model(): string;

    /** Whether this provider can generate free text / JSON with a language model. */
    public function supportsGeneration(): bool;

    /**
     * Ask the model for a JSON object.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array<string, mixed>
     *
     * @throws AIProviderException
     */
    public function generateJson(string $system, array $messages, float $temperature = 0.2): array;

    /** Speech to text. Returns null when the provider cannot transcribe. */
    public function transcribe(string $absolutePath, ?string $language = null): ?string;
}
