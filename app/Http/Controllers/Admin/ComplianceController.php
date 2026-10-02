<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Compliance\Actions\DecideCollaborationRequest;
use App\Domain\Compliance\Actions\ProcessDataRequest;
use App\Domain\Compliance\Actions\RecordComplaint;
use App\Domain\Compliance\Models\CollaborationRequest;
use App\Domain\Compliance\Models\Complaint;
use App\Domain\Compliance\Models\DataRequest;
use App\Domain\Compliance\Models\ServicePath;
use App\Domain\Identity\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Legal review of sensitive service paths, data-subject requests and complaints. */
class ComplianceController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Admin/Compliance', [
            'tab' => $request->query('tab', $user->can('legal.review') ? 'legal' : 'complaints'),
            'can' => ['legal' => $user->can('legal.review'), 'data' => $user->can('data_requests.manage'), 'complaints' => $user->can('complaints.manage')],
            'requests' => $user->can('legal.review') ? CollaborationRequest::with(['case', 'servicePath', 'requester', 'reviewer'])
                ->orderByRaw("case status when 'pending' then 0 else 1 end")->latest()->limit(100)->get()->map->toCard() : [],
            'paths' => ServicePath::orderBy('sort_order')->get()->map->toOption(),
            'dataRequests' => $user->can('data_requests.manage') ? DataRequest::with(['user', 'handler'])->latest()->limit(100)->get()->map(fn ($d) => [
                'id' => $d->id, 'type' => $d->type, 'status' => $d->status, 'reason' => $d->reason, 'resolution' => $d->resolution,
                'user' => $d->user?->only(['id', 'name', 'email']), 'handler' => $d->handler?->name, 'created_at' => $d->created_at->toIso8601String(),
            ]) : [],
            'complaints' => $user->can('complaints.manage') ? Complaint::with(['user', 'case', 'assignee'])
                ->orderByRaw("case status when 'open' then 0 when 'in_review' then 1 else 2 end")->latest()->limit(100)->get()->map->toCard() : [],
        ]);
    }

    public function decideRequest(Request $request, CollaborationRequest $collaboration, DecideCollaborationRequest $action): RedirectResponse
    {
        abort_unless($request->user()->can('legal.review'), 403);
        $data = $request->validate(['approve' => ['required', 'boolean'], 'conditions' => ['nullable', 'string', 'max:3000']]);
        $action->handle($collaboration, $request->user(), $data['approve'], $data['conditions'] ?? null);

        return back()->with('success', __('app.saved'));
    }

    public function updatePath(Request $request, ServicePath $path, AuditLogger $audit): RedirectResponse
    {
        abort_unless($request->user()->can('legal.review'), 403);
        $data = $request->validate(['mode' => ['required', Rule::in(ServicePath::MODES)], 'required_documents' => ['array'], 'required_documents.*' => ['string', 'max:200']]);
        $path->update($data);
        $audit->log('compliance.path.updated', null, ['path' => $path->key, 'mode' => $data['mode']]);

        return back()->with('success', __('app.saved'));
    }

    public function decideDataRequest(Request $request, DataRequest $dataRequest, ProcessDataRequest $action): RedirectResponse
    {
        abort_unless($request->user()->can('data_requests.manage') && $dataRequest->type === 'delete' && $dataRequest->status === 'pending', 403);
        $data = $request->validate(['approve' => ['required', 'boolean'], 'resolution' => ['required', 'string', 'max:2000']]);
        $action->decideDeletion($dataRequest, $request->user(), $data['approve'], $data['resolution']);

        return back()->with('success', __('app.saved'));
    }

    public function updateComplaint(Request $request, Complaint $complaint, RecordComplaint $action): RedirectResponse
    {
        abort_unless($request->user()->can('complaints.manage'), 403);
        $data = $request->validate(['status' => ['required', Rule::in(Complaint::STATUSES)], 'resolution' => ['nullable', 'required_if:status,resolved,rejected', 'string', 'max:3000']]);
        $action->update($complaint, $request->user(), $data);

        return back()->with('success', __('app.saved'));
    }
}
