<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/** Closing a case without a recorded outcome is not allowed. */
class CloseCase
{
    public function __construct(private readonly TransitionCaseStatus $transition) {}

    public function handle(SupportCase $case, User $user, ?string $reason = null): SupportCase
    {
        if (! $case->outcome()->exists()) {
            throw ValidationException::withMessages(['outcome' => __('cases.errors.outcome_required')]);
        }
        if ($case->status === CaseStatus::InProgress) {
            $this->transition->handle($case, CaseStatus::Resolved, $reason, $user->id);
        }

        $case->activeExperts()->newPivotStatement()->where('case_id', $case->id)->update(['status' => 'completed', 'left_at' => now()]);

        return $this->transition->handle($case, CaseStatus::Closed, $reason, $user->id);
    }
}
