<?php

namespace App\Support;

/**
 * Stores translatable attributes as controlled JSON columns: {"fa": "...", "en": "..."}.
 *
 * @property array<int, string> $translatable
 */
trait HasTranslations
{
    public function initializeHasTranslations(): void
    {
        foreach ($this->translatable as $attribute) {
            $this->casts[$attribute] = 'array';
        }
    }

    public function translate(string $attribute, ?string $locale = null): ?string
    {
        $values = $this->getAttribute($attribute) ?? [];
        $locale ??= app()->getLocale();

        return $values[$locale] ?? $values[config('app.fallback_locale')] ?? (count($values) ? reset($values) : null);
    }

    /** @return array<string, string|null> */
    public function translations(string $attribute): array
    {
        $values = $this->getAttribute($attribute) ?? [];

        return collect(config('platform.locales'))->mapWithKeys(fn ($l) => [$l => $values[$l] ?? null])->all();
    }
}
