<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Enums\OutcomeType;
use App\Domain\Cases\Models\CaseOutcome;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;

/**
 * Records the result of a case. "Effective action started" and "fully resolved" are kept distinct
 * so they can be reported separately.
 */
class RecordOutcome
{
    public function __construct(private readonly CaseTimeline $timeline, private readonly TransitionCaseStatus $transition) {}

    /** @param array{outcome: string, reason: string, result_summary?: string|null} $data */
    public function handle(SupportCase $case, User $user, array $data): CaseOutcome
    {
        $outcome = CaseOutcome::updateOrCreate(['case_id' => $case->id], [
            'recorded_by' => $user->id,
            'outcome' => $data['outcome'],
            'reason' => $data['reason'],
            'result_summary' => $data['result_summary'] ?? null,
        ]);
        $this->timeline->record($case, 'outcome_recorded', ['outcome' => $data['outcome']], $user->id);

        $resolvedLike = in_array(OutcomeType::from($data['outcome']), [OutcomeType::Resolved, OutcomeType::PartiallyResolved, OutcomeType::EffectiveActionStarted], true);
        if ($resolvedLike && $case->status->canTransitionTo(CaseStatus::Resolved)) {
            $this->transition->handle($case, CaseStatus::Resolved, 'outcome:'.$data['outcome'], $user->id);
        }

        return $outcome;
    }
}
