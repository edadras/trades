<?php

namespace App\Domain\Matching\Actions;

use App\Domain\Cases\Actions\TransitionCaseStatus;
use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Matching\Enums\MatchStatus;
use App\Domain\Matching\Models\ExpertMatch;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Validation\ValidationException;

/** The business accepts (→ invitation sent to the expert) or rejects a proposed supporter. */
class DecideMatch
{
    public function __construct(
        private readonly TransitionCaseStatus $transition,
        private readonly CaseTimeline $timeline,
        private readonly RunMatching $matching,
        private readonly CaseNotifier $notifier,
    ) {}

    public function handle(ExpertMatch $match, User $user, bool $accept, ?string $reason = null): ExpertMatch
    {
        if ($match->status !== MatchStatus::Proposed) {
            throw ValidationException::withMessages(['match' => __('matching.errors.already_decided')]);
        }
        $case = $match->case;

        $match->update([
            'status' => $accept ? MatchStatus::Invited : MatchStatus::RejectedByBusiness,
            'decision_reason' => $reason,
            'business_decided_at' => now(),
        ]);
        $this->timeline->record($case, $accept ? 'expert_selected' : 'expert_rejected', ['score' => $match->score, 'reason' => $reason], $user->id);

        if ($accept) {
            $expertUser = $match->expertProfile->user;
            $expertUser->notify(new PlatformNotification('expert_invited', ['case' => $case->number], route('expert.invitations.index', ['locale' => $expertUser->locale ?: 'fa'])));

            return $match;
        }

        $remaining = $case->matches()->whereIn('status', [MatchStatus::Proposed->value, MatchStatus::Invited->value, MatchStatus::Active->value])->count();
        if ($remaining === 0) {
            if ($case->status === CaseStatus::ExpertProposed) {
                $this->transition->handle($case, CaseStatus::Matching, 'all_rejected', $user->id);
            }
            $this->matching->handle($case->fresh(), $user->id);
        }

        return $match;
    }
}
