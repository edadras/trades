<?php

namespace App\Domain\Compliance\Actions;

use App\Domain\Compliance\Models\Complaint;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;
use App\Notifications\PlatformNotification;

class RecordComplaint
{
    /** @param array{category: string, subject: string, body: string, case_id?: int|null} $data */
    public function handle(User $user, array $data): Complaint
    {
        $complaint = Complaint::create($data + ['user_id' => $user->id]);

        foreach (User::permission(Permission::ComplaintsManage->value)->get() as $staff) {
            $staff->notify(new PlatformNotification('complaint_received', ['subject' => $complaint->subject], route('admin.compliance.index', ['locale' => $staff->locale ?: 'fa', 'tab' => 'complaints'])));
        }

        return $complaint;
    }

    /** @param array{status: string, resolution?: string|null, assignee_id?: int|null} $data */
    public function update(Complaint $complaint, User $staff, array $data): Complaint
    {
        $closing = in_array($data['status'], ['resolved', 'rejected'], true);
        $complaint->update([
            'status' => $data['status'],
            'resolution' => $data['resolution'] ?? $complaint->resolution,
            'assignee_id' => $data['assignee_id'] ?? $complaint->assignee_id ?? $staff->id,
            'resolved_at' => $closing ? now() : null,
        ]);

        if ($closing && $complaint->user) {
            $complaint->user->notify(new PlatformNotification('complaint_updated', ['subject' => $complaint->subject, 'status' => $complaint->status], route('support.index', ['locale' => $complaint->user->locale ?: 'fa'])));
        }

        return $complaint;
    }
}
