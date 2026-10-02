<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Matching\Actions\RunMatching;
use App\Domain\Matching\Enums\MatchStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Changes to the expert team of a case: an expert leaves, staff remove an expert, or the business asks
 * for a replacement. When no active expert remains the case goes back to matching.
 */
class ChangeCaseTeam
{
    public function __construct(
        private readonly TransitionCaseStatus $transition,
        private readonly CaseTimeline $timeline,
        private readonly CaseNotifier $notifier,
        private readonly RunMatching $matching,
    ) {}

    /** @param string $mode left | removed | replacement_requested */
    public function release(SupportCase $case, ExpertProfile $expert, User $actor, string $mode, string $reason): void
    {
        DB::transaction(function () use ($case, $expert, $actor, $mode, $reason) {
            $case->caseExperts()->where('expert_profile_id', $expert->id)->where('status', 'active')
                ->update(['status' => $mode === 'left' ? 'left' : 'removed', 'left_at' => now(), 'leave_reason' => $reason]);
            $case->matches()->where('expert_profile_id', $expert->id)->update(['status' => MatchStatus::Withdrawn->value, 'decision_reason' => $reason]);
            $case->conversation?->members()->where('user_id', $expert->user_id)->delete();
            $this->timeline->record($case, 'expert_'.$mode, ['expert' => $expert->user->name, 'reason' => $reason], $actor->id);
        });

        $this->notifier->notifyParticipants($case, 'expert_left', ['expert' => $expert->user->name], $actor->id);
        if ($actor->id !== $expert->user_id) {
            $this->notifier->notifyUser($expert->user, $case, 'expert_left', ['expert' => $expert->user->name], route('expert.dashboard', ['locale' => $expert->user->locale ?: 'fa']));
        }

        $case->refresh();
        if (! $case->activeExperts()->exists() && in_array($case->status, [CaseStatus::Accepted, CaseStatus::InProgress, CaseStatus::Waiting], true)) {
            $this->transition->handle($case, CaseStatus::Matching, 'team_changed', $actor->id);
            $this->matching->handle($case->fresh(), $actor->id);
        }
    }
}
