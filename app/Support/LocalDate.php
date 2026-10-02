<?php

namespace App\Support;

use Carbon\CarbonInterface;
use IntlDateFormatter;

/** Formats a date in the recipient's time zone, using the Persian (Jalali) calendar for Persian speakers. */
class LocalDate
{
    public static function format(?CarbonInterface $date, ?object $user = null, bool $withTime = true): string
    {
        if (! $date) {
            return '';
        }
        $locale = $user->locale ?? app()->getLocale();
        $timezone = $user->timezone ?? 'Asia/Tehran';
        $calendar = $locale === 'fa' ? 'fa_IR@calendar=persian' : 'en_GB';

        $formatter = new IntlDateFormatter($calendar, IntlDateFormatter::MEDIUM, $withTime ? IntlDateFormatter::SHORT : IntlDateFormatter::NONE, $timezone, $locale === 'fa' ? IntlDateFormatter::TRADITIONAL : IntlDateFormatter::GREGORIAN);

        return (string) $formatter->format($date->getTimestamp());
    }
}
