<?php

namespace App\Domain\Experts\Enums;

enum ExpertVerificationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
}
