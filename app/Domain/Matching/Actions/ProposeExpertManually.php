<?php

namespace App\Domain\Matching\Actions;

use App\Domain\Cases\Actions\TransitionCaseStatus;
use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Matching\Enums\MatchStatus;
use App\Domain\Matching\MatchingEngine;
use App\Domain\Matching\Models\ExpertMatch;
use App\Models\User;

/** A case expert assigns/proposes a specific supporter; the score and reasons are still computed. */
class ProposeExpertManually
{
    public function __construct(
        private readonly MatchingEngine $engine,
        private readonly TransitionCaseStatus $transition,
        private readonly CaseTimeline $timeline,
        private readonly CaseNotifier $notifier,
    ) {}

    public function handle(SupportCase $case, ExpertProfile $expert, User $staff): ExpertMatch
    {
        $expert->loadMissing(['skills', 'languages', 'availability', 'user']);
        $scored = $expert->skills->whereIn('case_category_id', array_filter([$case->category_id, $case->subcategory_id]))->isNotEmpty()
            ? $this->engine->score($expert, $case, ['total' => 0, 'success' => 0])
            : null;

        $match = ExpertMatch::updateOrCreate(['case_id' => $case->id, 'expert_profile_id' => $expert->id], [
            'score' => $scored['score'] ?? 0,
            'breakdown' => $scored['breakdown'] ?? [],
            'reasons' => $scored['reasons'] ?? [],
            'status' => MatchStatus::Proposed,
            'source' => 'staff',
            'proposed_by' => $staff->id,
        ]);

        foreach ([CaseStatus::Matching, CaseStatus::ExpertProposed] as $step) {
            if ($case->status->canTransitionTo($step)) {
                $this->transition->handle($case, $step, 'manual_proposal', $staff->id);
            }
        }
        $this->timeline->record($case, 'expert_proposed_manually', ['expert' => $expert->user->name], $staff->id);
        $this->notifier->notifyUser($case->business->owner, $case, 'expert_suggested', ['count' => 1]);

        return $match;
    }
}
