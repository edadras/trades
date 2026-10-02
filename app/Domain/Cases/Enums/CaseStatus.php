<?php

namespace App\Domain\Cases\Enums;

enum CaseStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case AiProcessing = 'ai_processing';
    case HumanReview = 'human_review';
    case Ready = 'ready';
    case Matching = 'matching';
    case ExpertProposed = 'expert_proposed';
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case Waiting = 'waiting';
    case Resolved = 'resolved';
    case Closed = 'closed';

    /**
     * Allowed transitions of the case state machine.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::AiProcessing, self::HumanReview],
            self::AiProcessing => [self::HumanReview, self::Ready, self::Submitted],
            self::HumanReview => [self::Ready, self::Waiting, self::Matching, self::Closed],
            self::Ready => [self::Matching, self::HumanReview, self::Resolved, self::Closed],
            self::Matching => [self::ExpertProposed, self::HumanReview, self::Closed],
            self::ExpertProposed => [self::Accepted, self::Matching, self::Closed],
            self::Accepted => [self::InProgress, self::Matching],
            self::InProgress => [self::Waiting, self::Resolved, self::Matching],
            self::Waiting => [self::InProgress, self::HumanReview, self::Resolved, self::Closed, self::Matching],
            self::Resolved => [self::Closed, self::InProgress, self::HumanReview],
            self::Closed => [self::InProgress, self::HumanReview],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Draft, self::Resolved, self::Closed], true);
    }

    /**
     * Position of this status on the 7-step stepper shown to users.
     * 1 Submit, 2 Complete info, 3 AI analysis, 4 Expert review, 5 Choose supporter, 6 Action, 7 Result.
     */
    public function step(): int
    {
        return match ($this) {
            self::Draft => 1,
            self::Submitted => 2,
            self::AiProcessing => 3,
            self::HumanReview => 4,
            self::Ready, self::Matching, self::ExpertProposed => 5,
            self::Accepted, self::InProgress, self::Waiting => 6,
            self::Resolved, self::Closed => 7,
        };
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
