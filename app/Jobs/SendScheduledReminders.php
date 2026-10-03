<?php

namespace App\Jobs;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\Appointment;
use App\Domain\Cases\Models\CaseTask;
use App\Models\User;
use App\Support\LocalDate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Deadline-approaching reminders for tasks (24h before) and appointment reminders (24h and 1h before). */
class SendScheduledReminders implements ShouldQueue
{
    use Queueable;

    public function handle(CaseNotifier $notifier): void
    {
        $openCase = fn ($q) => $q->whereNotIn('status', [CaseStatus::Closed->value, CaseStatus::Resolved->value]);
        CaseTask::query()->pending()->whereNull('reminded_at')->whereNotNull('assignee_id')->whereHas('case', $openCase)
            ->whereBetween('due_at', [now(), now()->addDay()])->with(['case.business', 'assignee'])
            ->each(function (CaseTask $task) use ($notifier) {
                // Only people still on the case are reminded (an expert may have left since the task was assigned).
                if (! $notifier->participants($task->case)->contains('id', $task->assignee_id)) {
                    return;
                }
                $notifier->notifyUser($task->assignee, $task->case, 'deadline_approaching', ['title' => $task->title, 'due' => LocalDate::format($task->due_at, $task->assignee)]);
                $task->update(['reminded_at' => now()]);
            });

        Appointment::query()->where('status', 'scheduled')->whereHas('case', $openCase)
            ->where(fn ($q) => $q->whereNull('reminded_at')->orWhere('reminded_at', '<', now()->subHours(2)))
            ->where(fn ($q) => $q->whereBetween('starts_at', [now()->addMinutes(45), now()->addMinutes(75)])
                ->orWhereBetween('starts_at', [now()->addHours(23), now()->addHours(25)]))
            ->with('case.business')
            ->each(function (Appointment $appointment) use ($notifier) {
                $current = $notifier->participants($appointment->case)->pluck('id');
                foreach (User::whereIn('id', $appointment->attendee_ids ?? [])->whereIn('id', $current)->get() as $user) {
                    $notifier->notifyUser($user, $appointment->case, 'appointment_reminder', ['title' => $appointment->title, 'when' => LocalDate::format($appointment->starts_at, $user)]);
                }
                $appointment->update(['reminded_at' => now()]);
            });
    }
}
