<?php

namespace App\Http\Controllers\Cases;

use App\Domain\Cases\Models\SupportCase;
use App\Domain\Matching\Actions\DecideMatch;
use App\Domain\Matching\Models\ExpertMatch;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MatchController extends Controller
{
    public function decide(Request $request, SupportCase $case, ExpertMatch $match, DecideMatch $action): RedirectResponse
    {
        Gate::authorize('decideMatches', $case);
        abort_unless($match->case_id === $case->id, 404);
        $data = $request->validate(['accept' => ['required', 'boolean'], 'reason' => ['nullable', 'string', 'max:500']]);
        $action->handle($match, $request->user(), $data['accept'], $data['reason'] ?? null);

        return back()->with('success', __('app.saved'));
    }
}
