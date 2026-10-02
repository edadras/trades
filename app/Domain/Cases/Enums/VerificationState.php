<?php

namespace App\Domain\Cases\Enums;

/**
 * Provenance of a classification or recommendation. The UI must always show
 * whether something is only an AI suggestion or has been checked by a human.
 */
enum VerificationState: string
{
    case AiSuggested = 'ai_suggested';
    case ExpertVerified = 'expert_verified';
    case HumanApproved = 'human_approved';
}
