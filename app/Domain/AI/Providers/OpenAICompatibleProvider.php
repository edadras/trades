<?php

namespace App\Domain\AI\Providers;

use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAICompatibleProvider implements AIProviderInterface
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config) {}

    public function name(): string
    {
        return 'openai';
    }

    public function model(): string
    {
        return $this->config['model'];
    }

    public function supportsGeneration(): bool
    {
        return ! empty($this->config['api_key']);
    }

    public function generateJson(string $system, array $messages, float $temperature = 0.2): array
    {
        try {
            $response = Http::baseUrl(rtrim($this->config['base_url'], '/'))
                ->withToken($this->config['api_key'])
                ->timeout($this->config['timeout'] ?? 45)
                ->retry(2, 500, throw: false)
                ->post('/chat/completions', [
                    'model' => $this->model(),
                    'temperature' => $temperature,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => array_merge([['role' => 'system', 'content' => $system]], $messages),
                ]);
        } catch (Throwable $e) {
            throw new AIProviderException('AI provider unreachable: '.$e->getMessage(), previous: $e);
        }

        if ($response->failed()) {
            throw new AIProviderException('AI provider error: HTTP '.$response->status());
        }

        $content = $response->json('choices.0.message.content');
        $decoded = is_string($content) ? json_decode($content, true) : null;

        if (! is_array($decoded)) {
            throw new AIProviderException('AI provider returned invalid JSON.');
        }

        return $decoded;
    }

    public function transcribe(string $absolutePath, ?string $language = null): ?string
    {
        if (! $this->supportsGeneration() || ! is_file($absolutePath)) {
            return null;
        }

        try {
            $response = Http::baseUrl(rtrim($this->config['base_url'], '/'))
                ->withToken($this->config['api_key'])
                ->timeout(120)
                ->attach('file', fopen($absolutePath, 'r'), basename($absolutePath))
                ->post('/audio/transcriptions', array_filter([
                    'model' => $this->config['transcription_model'],
                    'language' => $language,
                ]));
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? trim((string) $response->json('text')) ?: null : null;
    }
}
