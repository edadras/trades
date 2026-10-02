<?php

namespace App\Domain\Cases\Enums;

enum ParticipantRole: string
{
    case Business = 'business';
    case Expert = 'expert';
    case CaseManager = 'case_manager';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
