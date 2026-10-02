<?php

return [
    // "openai" (any OpenAI-compatible endpoint) or "local" (deterministic engine, no external calls).
    // When no API key is configured the local engine is used automatically.
    'default' => env('AI_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('AI_API_KEY'),
            'model' => env('AI_MODEL', 'gpt-4o-mini'),
            'transcription_model' => env('AI_TRANSCRIPTION_MODEL', 'whisper-1'),
            'timeout' => (int) env('AI_TIMEOUT', 45),
        ],
        'local' => [
            'model' => 'heuristic-v1',
        ],
    ],

    // Below this confidence the case goes to the human review queue.
    'confidence_threshold' => (float) env('AI_CONFIDENCE_THRESHOLD', 0.75),

    // How many follow-up questions the intake interview may ask.
    'max_intake_questions' => (int) env('AI_MAX_INTAKE_QUESTIONS', 4),

    // Strip emails, phone numbers and IDs before sending text to an external provider.
    'redact_pii' => (bool) env('AI_REDACT_PII', true),

    'knowledge' => [
        'max_results' => 5,
        'min_score' => 0.3,
    ],
];
