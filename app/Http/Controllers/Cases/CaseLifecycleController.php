<?php

namespace App\Http\Controllers\Cases;

use App\Domain\Cases\Actions\ChangeCaseTeam;
use App\Domain\Cases\Actions\ConfirmOutcome;
use App\Domain\Cases\Actions\ReopenCase;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Compliance\Actions\RequestCollaboration;
use App\Domain\Compliance\Models\ServicePath;
use App\Domain\Experts\Models\ExpertProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/** Outcome confirmation, reopening, case details, service-path requests and team changes. */
class CaseLifecycleController extends Controller
{
    public function confirmOutcome(Request $request, SupportCase $case, ConfirmOutcome $action): RedirectResponse
    {
        Gate::authorize('confirmOutcome', $case);
        $data = $request->validate(['confirm' => ['required', 'boolean'], 'reason' => ['required_if:confirm,false', 'nullable', 'string', 'max:2000']]);
        $outcome = $case->outcome()->firstOrFail();
        $data['confirm'] ? $action->confirm($outcome, $request->user()) : $action->dispute($outcome, $request->user(), $data['reason']);

        return back()->with('success', __('app.saved'));
    }

    public function reopen(Request $request, SupportCase $case, ReopenCase $action): RedirectResponse
    {
        Gate::authorize('reopen', $case);
        $action->handle($case, $request->user(), $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']])['reason']);

        return back()->with('success', __('app.saved'));
    }

    /** The business keeps the structured "actions already taken" up to date. */
    public function updateDetails(Request $request, SupportCase $case, CaseTimeline $timeline): RedirectResponse
    {
        abort_unless($case->business->hasMember($request->user()) && ! $case->isClosed(), 403);
        $data = $request->validate(['actions_taken' => ['nullable', 'string', 'max:4000']]);
        $case->update($data);
        $timeline->record($case, 'details_updated', ['field' => 'actions_taken']);

        return back()->with('success', __('app.saved'));
    }

    public function requestCollaboration(Request $request, SupportCase $case, RequestCollaboration $action): RedirectResponse
    {
        Gate::authorize('requestCollaboration', $case);
        $data = $request->validate(['service_path_id' => ['required', 'exists:service_paths,id'], 'description' => ['required', 'string', 'min:10', 'max:3000']]);
        $action->handle($case, $request->user(), ServicePath::findOrFail($data['service_path_id']), $data['description']);

        return back()->with('success', __('app.saved'));
    }

    /** An active expert steps away from the case. */
    public function leave(Request $request, SupportCase $case, ChangeCaseTeam $action): RedirectResponse
    {
        Gate::authorize('leave', $case);
        $reason = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']])['reason'];
        $action->release($case, $request->user()->expertProfile()->firstOrFail(), $request->user(), 'left', $reason);

        return redirect()->route('expert.dashboard')->with('success', __('app.saved'));
    }

    /** Staff remove an expert, or the business asks for a replacement. */
    public function releaseExpert(Request $request, SupportCase $case, ExpertProfile $expert, ChangeCaseTeam $action): RedirectResponse
    {
        $user = $request->user();
        Gate::authorize('releaseExperts', $case);
        $isBusiness = $case->business->hasMember($user);
        if (! $case->hasActiveExpert($expert->user)) {
            throw ValidationException::withMessages(['expert' => __('cases.errors.not_on_team')]);
        }
        $reason = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']])['reason'];
        $action->release($case, $expert, $user, $isBusiness ? 'replacement_requested' : 'removed', $reason);

        return back()->with('success', __('app.saved'));
    }
}
