<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Models\Appointment;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use App\Support\LocalDate;

class ScheduleAppointment
{
    public function __construct(private readonly CaseTimeline $timeline, private readonly CaseNotifier $notifier) {}

    /** @param array<string, mixed> $data */
    public function handle(SupportCase $case, User $organizer, array $data): Appointment
    {
        $appointment = Appointment::create([
            'case_id' => $case->id,
            'organizer_id' => $organizer->id,
            'title' => $data['title'],
            'agenda' => $data['agenda'] ?? null,
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'location' => $data['location'] ?? null,
            'meeting_url' => $data['meeting_url'] ?? null,
            'attendee_ids' => $this->notifier->participants($case)->pluck('id')->all(),
        ]);

        $this->timeline->record($case, 'appointment_scheduled', ['title' => $appointment->title, 'starts_at' => $appointment->starts_at->toIso8601String()], $organizer->id);
        foreach ($this->notifier->participants($case) as $participant) {
            if ($participant->id !== $organizer->id) {
                $this->notifier->notifyUser($participant, $case, 'appointment_scheduled', ['title' => $appointment->title, 'when' => LocalDate::format($appointment->starts_at, $participant)]);
            }
        }

        return $appointment;
    }
}
