<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\ParticipantRole;
use App\Domain\Cases\Models\CaseTask;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;

/** Creates a business-owned task asking for a document/information and notifies the business. */
class RequestCaseDocument
{
    public function __construct(private readonly CaseTimeline $timeline, private readonly CaseNotifier $notifier) {}

    public function handle(SupportCase $case, User $requester, string $what, ?string $dueAt = null): CaseTask
    {
        $task = CaseTask::create([
            'case_id' => $case->id,
            'created_by' => $requester->id,
            'assignee_id' => $case->business->owner_id,
            'title' => $what,
            'owner_role' => ParticipantRole::Business,
            'is_next_action' => true,
            'due_at' => $dueAt ?? now()->addDays(3),
        ]);
        $case->update(['next_action' => $what, 'next_action_owner' => 'business', 'next_action_due_at' => $task->due_at]);
        $this->timeline->record($case, 'document_requested', ['what' => $what], $requester->id);
        $this->notifier->notifyBusiness($case, 'document_requested', ['what' => $what]);

        return $task;
    }
}
