<?php

namespace App\Listeners;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\Enums\CaseStatus;
use App\Events\CaseStatusChanged;

class NotifyCaseStatusChanged
{
    public function __construct(private readonly CaseNotifier $notifier) {}

    public function handle(CaseStatusChanged $event): void
    {
        $eventKey = match ($event->to) {
            CaseStatus::Resolved, CaseStatus::Closed => 'case_resolved',
            CaseStatus::AiProcessing, CaseStatus::Submitted, CaseStatus::Draft => null,
            default => 'case_updated',
        };
        if ($eventKey) {
            $this->notifier->notifyParticipants($event->case, $eventKey, ['status' => $event->to->value]);
        }
    }
}
