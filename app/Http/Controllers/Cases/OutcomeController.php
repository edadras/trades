<?php

namespace App\Http\Controllers\Cases;

use App\Domain\Cases\Actions\CloseCase;
use App\Domain\Cases\Actions\RecordOutcome;
use App\Domain\Cases\Actions\SubmitSatisfaction;
use App\Domain\Cases\Enums\OutcomeType;
use App\Domain\Cases\Models\SupportCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OutcomeController extends Controller
{
    public function store(Request $request, SupportCase $case, RecordOutcome $action): RedirectResponse
    {
        Gate::authorize('recordOutcome', $case);
        $data = $request->validate([
            'outcome' => ['required', Rule::in(OutcomeType::values())],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'result_summary' => ['nullable', 'string', 'max:3000'],
        ]);
        $action->handle($case, $request->user(), $data);

        return back()->with('success', __('app.saved'));
    }

    public function close(Request $request, SupportCase $case, CloseCase $action): RedirectResponse
    {
        Gate::authorize('close', $case);
        $action->handle($case, $request->user(), $request->validate(['reason' => ['nullable', 'string', 'max:500']])['reason'] ?? null);

        return back()->with('success', __('cases.closed'));
    }

    public function satisfaction(Request $request, SupportCase $case, SubmitSatisfaction $action): RedirectResponse
    {
        Gate::authorize('rate', $case);
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'problem_solved' => ['nullable', 'boolean'],
            'would_recommend_expert' => ['nullable', 'boolean'],
        ]);
        $action->handle($case, $request->user(), $data);

        return back()->with('success', __('app.saved'));
    }
}
