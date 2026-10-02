<?php

namespace App\Domain\Compliance\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Compliance\Models\CollaborationRequest;
use App\Domain\Identity\AuditLogger;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DecideCollaborationRequest
{
    public function __construct(private readonly CaseTimeline $timeline, private readonly CaseNotifier $notifier, private readonly AuditLogger $audit) {}

    public function handle(CollaborationRequest $request, User $reviewer, bool $approve, ?string $conditions = null): CollaborationRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['status' => __('compliance.errors.already_decided')]);
        }

        $request->update([
            'status' => $approve ? 'approved' : 'rejected',
            'reviewer_id' => $reviewer->id,
            'conditions' => $conditions,
            'reviewed_at' => now(),
        ]);

        $case = $request->case;
        $this->timeline->record($case, 'collaboration_'.$request->status, ['path' => $request->servicePath->key, 'conditions' => $conditions], $reviewer->id);
        $this->audit->log('compliance.request.'.$request->status, $case, ['path' => $request->servicePath->key]);
        $this->notifier->notifyParticipants($case, 'collaboration_request_decided', [
            'path' => $request->servicePath->translate('name'),
            'status' => $request->status,
        ], $reviewer->id);

        return $request;
    }
}
