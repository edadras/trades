<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Closing requires an outcome confirmed by the business (or auto-confirmed after the confirmation window). */
class CloseCase
{
    public function __construct(private readonly TransitionCaseStatus $transition) {}

    public function handle(SupportCase $case, User $user, ?string $reason = null): SupportCase
    {
        if (! $case->hasConfirmedOutcome()) {
            throw ValidationException::withMessages(['outcome' => __('cases.errors.outcome_required')]);
        }
        if (in_array($case->status, [CaseStatus::InProgress, CaseStatus::Waiting], true)) {
            $this->transition->handle($case, CaseStatus::Resolved, $reason, $user->id);
        }

        $case->caseExperts()->where('status', 'active')->update(['status' => 'completed', 'left_at' => now()]);
        // Nothing should remind anyone about a closed case.
        $case->appointments()->where('status', 'scheduled')->where('starts_at', '>', now())->update(['status' => 'cancelled']);

        return $this->transition->handle($case, CaseStatus::Closed, $reason, $user->id);
    }
}
