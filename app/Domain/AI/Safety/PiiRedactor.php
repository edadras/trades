<?php

namespace App\Domain\AI\Safety;

/** Removes direct identifiers before text leaves the platform for an external model. */
class PiiRedactor
{
    public function redact(string $text): string
    {
        if (! config('ai.redact_pii')) {
            return $text;
        }

        $patterns = [
            '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i' => '[email]',
            '/\bIR\d{24}\b/i' => '[iban]',
            '/(?:\+|00)?\d[\d\s\-]{8,14}\d/' => '[phone]',
            '/\b\d{10}\b/' => '[id]',
            '/\b(?:\d[ -]*?){16}\b/' => '[card]',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $text) ?? $text;
    }
}
