<?php

namespace App\Domain\Matching\Actions;

use App\Domain\Cases\Actions\TransitionCaseStatus;
use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Matching\Enums\MatchStatus;
use App\Domain\Matching\MatchingEngine;
use App\Domain\Matching\Models\ExpertMatch;
use Illuminate\Support\Collection;

/** Moves the case to Matching, stores ranked proposals and asks the business to pick one. */
class RunMatching
{
    public function __construct(
        private readonly MatchingEngine $engine,
        private readonly TransitionCaseStatus $transition,
        private readonly CaseTimeline $timeline,
        private readonly CaseNotifier $notifier,
    ) {}

    /** @return Collection<int, ExpertMatch> */
    public function handle(SupportCase $case, ?int $userId = null): Collection
    {
        if ($case->status->canTransitionTo(CaseStatus::Matching)) {
            $this->transition->handle($case, CaseStatus::Matching, null, $userId);
        }

        $ranked = $this->engine->rank($case);
        $matches = $ranked->map(fn ($r) => ExpertMatch::updateOrCreate(
            ['case_id' => $case->id, 'expert_profile_id' => $r['expert']->id],
            ['score' => $r['score'], 'breakdown' => $r['breakdown'], 'reasons' => $r['reasons'], 'status' => MatchStatus::Proposed, 'source' => 'engine'],
        ));

        $this->timeline->record($case, 'matching_completed', ['count' => $matches->count()], $userId);

        if ($matches->isNotEmpty()) {
            if ($case->status === CaseStatus::Matching) {
                $this->transition->handle($case, CaseStatus::ExpertProposed, null, $userId);
            }
            $this->notifier->notifyUser($case->business->owner, $case, 'expert_suggested', ['count' => $matches->count()]);
        } else {
            // No suitable verified expert: operations must source one manually.
            $this->timeline->record($case, 'matching_no_candidates', [], $userId, 'internal');
        }

        return $matches;
    }
}
