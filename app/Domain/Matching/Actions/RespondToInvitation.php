<?php

namespace App\Domain\Matching\Actions;

use App\Domain\Cases\Actions\TransitionCaseStatus;
use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\CaseExpert;
use App\Domain\Identity\AuditLogger;
use App\Domain\Matching\Enums\MatchStatus;
use App\Domain\Matching\Models\ExpertMatch;
use App\Domain\Messaging\Actions\EnsureCaseWorkspace;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The invited expert accepts (joins the case, workspace opens, case moves to In Progress) or declines.
 * Confidential business information only becomes visible after acceptance.
 */
class RespondToInvitation
{
    public function __construct(
        private readonly TransitionCaseStatus $transition,
        private readonly CaseTimeline $timeline,
        private readonly CaseNotifier $notifier,
        private readonly EnsureCaseWorkspace $workspace,
        private readonly RunMatching $matching,
        private readonly AuditLogger $audit,
    ) {}

    public function handle(ExpertMatch $match, User $expertUser, bool $accept, ?string $reason = null): ExpertMatch
    {
        if ($match->status !== MatchStatus::Invited) {
            throw ValidationException::withMessages(['match' => __('matching.errors.not_invited')]);
        }
        $case = $match->case;

        if (! $accept) {
            $match->update(['status' => MatchStatus::ExpertDeclined, 'decision_reason' => $reason, 'expert_decided_at' => now()]);
            $this->timeline->record($case, 'expert_declined', [], $expertUser->id);
            $this->notifier->notifyUser($case->business->owner, $case, 'expert_declined', []);
            $open = $case->matches()->whereIn('status', [MatchStatus::Proposed->value, MatchStatus::Invited->value, MatchStatus::Active->value])->count();
            if ($open === 0) {
                if ($case->status === CaseStatus::ExpertProposed) {
                    $this->transition->handle($case, CaseStatus::Matching, 'expert_declined', $expertUser->id);
                }
                $this->matching->handle($case->fresh(), $expertUser->id);
            }

            return $match;
        }

        DB::transaction(function () use ($match, $case, $expertUser) {
            $match->update(['status' => MatchStatus::Active, 'expert_decided_at' => now()]);
            CaseExpert::updateOrCreate(['case_id' => $case->id, 'expert_profile_id' => $match->expert_profile_id], [
                'expert_match_id' => $match->id,
                'role' => $case->caseExperts()->where('status', 'active')->exists() ? 'support' : 'lead',
                'status' => 'active',
                'joined_at' => now(),
            ]);
            // Other pending proposals are withdrawn once one expert is engaged.
            $case->matches()->where('id', '!=', $match->id)->where('status', MatchStatus::Proposed->value)->update(['status' => MatchStatus::Withdrawn->value]);
            $this->timeline->record($case, 'expert_joined', ['expert' => $expertUser->name], $expertUser->id);
        });

        $this->workspace->handle($case->fresh());
        $this->audit->log('case.confidential_access_granted', $case, ['expert_user_id' => $expertUser->id], $expertUser->id);

        foreach ([CaseStatus::Accepted, CaseStatus::InProgress] as $step) {
            if ($case->fresh()->status->canTransitionTo($step)) {
                $this->transition->handle($case->fresh(), $step, null, $expertUser->id);
            }
        }
        $this->notifier->notifyParticipants($case->fresh(), 'expert_accepted', ['expert' => $expertUser->name], $expertUser->id);

        return $match;
    }
}
