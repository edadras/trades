<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\ParticipantRole;
use App\Domain\Cases\Enums\TaskStatus;
use App\Domain\Cases\Models\CaseTask;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;

class SaveCaseTask
{
    public function __construct(private readonly CaseTimeline $timeline, private readonly CaseNotifier $notifier) {}

    /** @param array<string, mixed> $data */
    public function create(SupportCase $case, User $user, array $data): CaseTask
    {
        $assignee = $data['assignee_id'] ?? $this->defaultAssignee($case, ParticipantRole::from($data['owner_role']));
        $task = CaseTask::create([
            'case_id' => $case->id,
            'created_by' => $user->id,
            'assignee_id' => $assignee,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'owner_role' => $data['owner_role'],
            'is_next_action' => (bool) ($data['is_next_action'] ?? false),
            'due_at' => $data['due_at'] ?? null,
        ]);

        if ($task->is_next_action) {
            $this->setNextAction($case, $task);
        }
        $this->timeline->record($case, 'task_created', ['title' => $task->title, 'owner' => $task->owner_role->value], $user->id);
        if ($assignee && $assignee !== $user->id) {
            $this->notifier->notifyUser(User::find($assignee), $case, 'task_assigned', ['title' => $task->title]);
        }

        return $task;
    }

    public function updateStatus(CaseTask $task, User $user, TaskStatus $status): CaseTask
    {
        $task->update(['status' => $status, 'completed_at' => $status === TaskStatus::Done ? now() : null]);
        $case = $task->case;
        $this->timeline->record($case, 'task_'.$status->value, ['title' => $task->title], $user->id);

        if ($status === TaskStatus::Done && $task->is_next_action) {
            $next = $case->tasks()->pending()->where('is_next_action', true)->orderBy('due_at')->first();
            $next ? $this->setNextAction($case, $next) : $case->update(['next_action' => null, 'next_action_owner' => null, 'next_action_due_at' => null]);
        }

        return $task;
    }

    private function setNextAction(SupportCase $case, CaseTask $task): void
    {
        $case->update(['next_action' => $task->title, 'next_action_owner' => $task->owner_role->value, 'next_action_due_at' => $task->due_at]);
    }

    private function defaultAssignee(SupportCase $case, ParticipantRole $role): ?int
    {
        return match ($role) {
            ParticipantRole::Business => $case->business->owner_id,
            ParticipantRole::Expert => $case->activeExperts()->first()?->user_id,
            ParticipantRole::CaseManager => $case->case_manager_id,
        };
    }
}
