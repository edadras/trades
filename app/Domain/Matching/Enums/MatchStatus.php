<?php

namespace App\Domain\Matching\Enums;

enum MatchStatus: string
{
    case Proposed = 'proposed';
    case RejectedByBusiness = 'rejected_by_business';
    case Invited = 'invited';
    case ExpertDeclined = 'expert_declined';
    case Active = 'active';
    case Withdrawn = 'withdrawn';
}
