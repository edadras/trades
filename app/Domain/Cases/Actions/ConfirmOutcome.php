<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Enums\OutcomeType;
use App\Domain\Cases\Models\CaseOutcome;
use App\Domain\Compliance\Actions\RecordComplaint;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** The business confirms or disputes a proposed outcome. A dispute opens a complaint for operations. */
class ConfirmOutcome
{
    public function __construct(
        private readonly CaseTimeline $timeline,
        private readonly TransitionCaseStatus $transition,
        private readonly CaseNotifier $notifier,
        private readonly RecordComplaint $complaints,
    ) {}

    public function confirm(CaseOutcome $outcome, ?User $user, string $event = 'outcome_confirmed'): CaseOutcome
    {
        $this->ensurePending($outcome);
        $outcome->update(['confirmation_status' => 'confirmed', 'confirmed_by' => $user?->id, 'confirmed_at' => now(), 'dispute_reason' => null]);
        $case = $outcome->case()->first();
        $this->timeline->record($case, $event, ['outcome' => $outcome->outcome->value], $user?->id);

        $resolvedLike = in_array($outcome->outcome, [OutcomeType::Resolved, OutcomeType::PartiallyResolved, OutcomeType::EffectiveActionStarted], true);
        if ($resolvedLike && $case->status->canTransitionTo(CaseStatus::Resolved)) {
            $this->transition->handle($case, CaseStatus::Resolved, 'outcome:'.$outcome->outcome->value, $user?->id);
        }
        $this->notifier->notifyParticipants($case->fresh(), $event, [], $user?->id);

        return $outcome;
    }

    public function dispute(CaseOutcome $outcome, User $user, string $reason): CaseOutcome
    {
        $this->ensurePending($outcome);
        $outcome->update(['confirmation_status' => 'disputed', 'dispute_reason' => $reason]);
        $case = $outcome->case()->first();
        $this->timeline->record($case, 'outcome_disputed', ['reason' => $reason], $user->id);

        $this->complaints->handle($user, [
            'case_id' => $case->id,
            'category' => 'outcome_dispute',
            'subject' => $case->number,
            'body' => $reason,
        ]);
        $this->notifier->notifyParticipants($case, 'outcome_disputed', ['reason' => $reason], $user->id);

        return $outcome;
    }

    private function ensurePending(CaseOutcome $outcome): void
    {
        if ($outcome->confirmation_status !== 'pending' || $outcome->superseded_at) {
            throw ValidationException::withMessages(['outcome' => __('cases.errors.outcome_not_pending')]);
        }
    }
}
