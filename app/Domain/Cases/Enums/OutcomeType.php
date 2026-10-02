<?php

namespace App\Domain\Cases\Enums;

enum OutcomeType: string
{
    case Resolved = 'resolved';
    case PartiallyResolved = 'partially_resolved';
    case EffectiveActionStarted = 'effective_action_started';
    case Unresolved = 'unresolved';
    case Abandoned = 'abandoned';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
