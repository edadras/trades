<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Models\CaseOutcome;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Records the result of a case. An outcome proposed by an expert or staff member stays "pending" until the
 * business confirms it; an outcome recorded by the business itself is confirmed immediately.
 * "Effective action started" and "fully resolved" are kept distinct so they can be reported separately.
 */
class RecordOutcome
{
    public function __construct(
        private readonly CaseTimeline $timeline,
        private readonly CaseNotifier $notifier,
        private readonly ConfirmOutcome $confirm,
    ) {}

    /** @param array{outcome: string, reason: string, result_summary?: string|null} $data */
    public function handle(SupportCase $case, User $user, array $data): CaseOutcome
    {
        $byBusiness = in_array($case->business->roleOf($user), ['owner', 'admin'], true);

        $outcome = DB::transaction(function () use ($case, $user, $data) {
            $case->outcomes()->whereNull('superseded_at')->update(['superseded_at' => now()]);

            return CaseOutcome::create([
                'case_id' => $case->id,
                'recorded_by' => $user->id,
                'outcome' => $data['outcome'],
                'reason' => $data['reason'],
                'result_summary' => $data['result_summary'] ?? null,
                'confirmation_status' => 'pending',
            ]);
        });

        $this->timeline->record($case, 'outcome_recorded', ['outcome' => $data['outcome']], $user->id);

        if ($byBusiness) {
            return $this->confirm->confirm($outcome, $user);
        }

        $this->notifier->notifyBusiness($case, 'outcome_confirmation_requested', ['outcome' => $data['outcome']], managersOnly: true);

        return $outcome;
    }
}
