<?php

namespace App\Domain\Compliance\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Compliance\Models\CollaborationRequest;
use App\Domain\Compliance\Models\ServicePath;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Asks legal & compliance to approve a sensitive service path (investment, fund transfer, commercial
 * contract, sensitive data exchange …) before it is activated on a case. Allowed paths need no review.
 */
class RequestCollaboration
{
    public function __construct(private readonly CaseTimeline $timeline, private readonly CaseNotifier $notifier) {}

    public function handle(SupportCase $case, ?User $user, ServicePath $path, string $description): CollaborationRequest
    {
        if ($path->mode === 'disabled') {
            throw ValidationException::withMessages(['service_path_id' => __('compliance.errors.path_disabled')]);
        }

        $existing = $case->collaborationRequests()->where('service_path_id', $path->id)->whereIn('status', ['pending', 'approved'])->first();
        if ($existing) {
            return $existing;
        }

        $request = CollaborationRequest::create([
            'case_id' => $case->id,
            'service_path_id' => $path->id,
            'requested_by' => $user?->id,
            'description' => $description,
            'status' => $path->mode === 'allowed' ? 'approved' : 'pending',
            'reviewed_at' => $path->mode === 'allowed' ? now() : null,
        ]);
        $this->timeline->record($case, 'collaboration_requested', ['path' => $path->key, 'status' => $request->status], $user?->id);

        if ($request->status === 'pending') {
            foreach (User::permission(Permission::LegalReview->value)->get() as $reviewer) {
                $this->notifier->notifyUser($reviewer, $case, 'legal_review_required', ['path' => $path->translate('name', $reviewer->locale ?: 'fa')], route('admin.compliance.index', ['locale' => $reviewer->locale ?: 'fa']));
            }
        }

        return $request;
    }
}
