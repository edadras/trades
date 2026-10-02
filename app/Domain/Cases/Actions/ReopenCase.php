<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Reopens a resolved/closed case ("final or updated" in the process). The previous outcome is kept
 * as history (superseded) and experts who completed the case rejoin it.
 */
class ReopenCase
{
    public const WINDOW_DAYS = 60;

    public function __construct(private readonly TransitionCaseStatus $transition, private readonly CaseTimeline $timeline, private readonly CaseNotifier $notifier) {}

    public function handle(SupportCase $case, User $user, string $reason): SupportCase
    {
        DB::transaction(function () use ($case, $user, $reason) {
            $case->outcomes()->whereNull('superseded_at')->update(['superseded_at' => now()]);
            $case->caseExperts()->where('status', 'completed')->update(['status' => 'active', 'left_at' => null]);
            $case->forceFill(['resolved_at' => null, 'closed_at' => null])->save();
            $this->timeline->record($case, 'case_reopened', ['reason' => $reason], $user->id);
        });

        $target = $case->activeExperts()->exists() ? CaseStatus::InProgress : CaseStatus::HumanReview;
        $this->transition->handle($case->fresh(), $target, 'reopened: '.$reason, $user->id);
        $this->notifier->notifyParticipants($case->fresh(), 'case_reopened', [], $user->id);

        return $case->fresh();
    }
}
