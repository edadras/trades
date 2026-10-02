<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\CaseStatusHistory;
use App\Domain\Cases\Models\SupportCase;
use App\Events\CaseStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The single entry point for changing a case status. Enforces the state machine,
 * stamps milestone timestamps, records history + timeline and broadcasts the change.
 */
class TransitionCaseStatus
{
    public function __construct(private readonly CaseTimeline $timeline) {}

    public function handle(SupportCase $case, CaseStatus $to, ?string $reason = null, ?int $userId = null): SupportCase
    {
        $from = $case->status;
        if ($from === $to) {
            return $case;
        }
        if (! $from->canTransitionTo($to)) {
            throw ValidationException::withMessages(['status' => __('cases.errors.invalid_transition', ['from' => __("cases.status.{$from->value}"), 'to' => __("cases.status.{$to->value}")])]);
        }
        if ($to === CaseStatus::Closed && ! $case->hasConfirmedOutcome()) {
            throw ValidationException::withMessages(['outcome' => __('cases.errors.outcome_required')]);
        }

        DB::transaction(function () use ($case, $from, $to, $reason, $userId) {
            $stamps = [
                CaseStatus::Submitted->value => 'submitted_at',
                CaseStatus::Ready->value => 'ready_at',
                CaseStatus::Accepted->value => 'accepted_at',
                CaseStatus::Resolved->value => 'resolved_at',
                CaseStatus::Closed->value => 'closed_at',
            ];
            $case->status = $to;
            if (isset($stamps[$to->value]) && is_null($case->{$stamps[$to->value]})) {
                $case->{$stamps[$to->value]} = now();
            }
            $case->save();

            CaseStatusHistory::create([
                'case_id' => $case->id,
                'user_id' => $userId ?? auth()->id(),
                'from_status' => $from->value,
                'to_status' => $to->value,
                'reason' => $reason,
            ]);
            $this->timeline->record($case, 'status_changed', ['from' => $from->value, 'to' => $to->value, 'reason' => $reason], $userId);
        });

        CaseStatusChanged::dispatch($case, $from, $to);

        return $case;
    }
}
