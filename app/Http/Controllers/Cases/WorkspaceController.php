<?php

namespace App\Http\Controllers\Cases;

use App\Domain\Cases\Actions\AddCaseNote;
use App\Domain\Cases\Actions\AnalyzeCase;
use App\Domain\Cases\Actions\RequestCaseDocument;
use App\Domain\Cases\Actions\SaveCaseTask;
use App\Domain\Cases\Actions\ScheduleAppointment;
use App\Domain\Cases\Actions\TransitionCaseStatus;
use App\Domain\Cases\Actions\UploadCaseDocument;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Enums\ParticipantRole;
use App\Domain\Cases\Enums\TaskStatus;
use App\Domain\Cases\Models\CaseTask;
use App\Domain\Cases\Models\SupportCase;
use App\Http\Controllers\Controller;
use App\Services\Files\SecureFileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/** Collaboration inside a case: documents, tasks/next actions, appointments, notes, waiting/resume. */
class WorkspaceController extends Controller
{
    public function uploadDocument(Request $request, SupportCase $case, UploadCaseDocument $upload, AnalyzeCase $analyze): RedirectResponse
    {
        Gate::authorize('participate', $case);
        $data = $request->validate(['files' => ['required', 'array', 'max:10'], 'files.*' => SecureFileStorage::documentRules(), 'title' => ['nullable', 'string', 'max:150']]);
        foreach ($request->file('files') as $file) {
            $upload->handle($case, $request->user(), $file, $data['title'] ?? null);
        }

        // Information requested during review has arrived: send the case back for review.
        if ($case->status === CaseStatus::Waiting && ! $case->activeExperts()->exists()) {
            app(TransitionCaseStatus::class)->handle($case, CaseStatus::HumanReview, 'information_received');
        }

        return back()->with('success', __('app.saved'));
    }

    public function requestDocument(Request $request, SupportCase $case, RequestCaseDocument $action): RedirectResponse
    {
        Gate::authorize('participate', $case);
        abort_if($case->business->hasMember($request->user()), 403);
        $data = $request->validate(['what' => ['required', 'string', 'max:300'], 'due_at' => ['nullable', 'date', 'after:now']]);
        $action->handle($case, $request->user(), $data['what'], $data['due_at'] ?? null);

        return back()->with('success', __('app.saved'));
    }

    public function storeTask(Request $request, SupportCase $case, SaveCaseTask $action): RedirectResponse
    {
        Gate::authorize('participate', $case);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'owner_role' => ['required', Rule::in(ParticipantRole::values())],
            'due_at' => ['nullable', 'date'],
            'is_next_action' => ['boolean'],
        ]);
        $action->create($case, $request->user(), $data);

        return back()->with('success', __('app.saved'));
    }

    public function updateTask(Request $request, SupportCase $case, CaseTask $task, SaveCaseTask $action): RedirectResponse
    {
        Gate::authorize('participate', $case);
        abort_unless($task->case_id === $case->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(TaskStatus::values())]]);
        $action->updateStatus($task, $request->user(), TaskStatus::from($data['status']));

        return back();
    }

    public function storeAppointment(Request $request, SupportCase $case, ScheduleAppointment $action): RedirectResponse
    {
        Gate::authorize('participate', $case);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'agenda' => ['nullable', 'string', 'max:2000'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:200'],
            'meeting_url' => ['nullable', 'url', 'max:300'],
        ]);
        $action->handle($case, $request->user(), $data);

        return back()->with('success', __('app.saved'));
    }

    public function storeNote(Request $request, SupportCase $case, AddCaseNote $action): RedirectResponse
    {
        Gate::authorize('participate', $case);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000'], 'visibility' => ['nullable', Rule::in(['internal', 'team'])]]);
        $action->handle($case, $request->user(), $data['body'], $data['visibility'] ?? 'team');

        return back()->with('success', __('app.saved'));
    }

    /** Pause / resume collaboration (e.g. waiting for the business to gather data). */
    public function setWaiting(Request $request, SupportCase $case, TransitionCaseStatus $transition): RedirectResponse
    {
        Gate::authorize('participate', $case);
        $data = $request->validate(['waiting' => ['required', 'boolean'], 'reason' => ['nullable', 'string', 'max:300']]);
        $transition->handle($case, $data['waiting'] ? CaseStatus::Waiting : CaseStatus::InProgress, $data['reason'] ?? null);

        return back();
    }
}
