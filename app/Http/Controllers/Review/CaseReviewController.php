<?php

namespace App\Http\Controllers\Review;

use App\Domain\AI\Models\AiHumanReview;
use App\Domain\Cases\Actions\AnalyzeCase;
use App\Domain\Cases\Actions\ApplyHumanReview;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\Urgency;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Matching\Actions\ProposeExpertManually;
use App\Domain\Matching\Actions\RunMatching;
use App\Domain\Matching\MatchingEngine;
use App\Domain\Messaging\Actions\EnsureCaseWorkspace;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CaseReviewController extends Controller
{
    public function decide(Request $request, SupportCase $case, ApplyHumanReview $action): RedirectResponse
    {
        Gate::authorize('review', $case);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['confirmed', 'edited', 'info_requested', 'escalated'])],
            'category_id' => ['nullable', 'exists:case_categories,id'],
            'subcategory_id' => ['nullable', 'exists:case_categories,id'],
            'urgency' => ['nullable', Rule::in(Urgency::values())],
            'notes' => ['nullable', 'string', 'max:3000'],
            'request' => ['nullable', 'required_if:decision,info_requested', 'string', 'max:500'],
            'run_matching' => ['boolean'],
        ]);
        $action->handle($case, $request->user(), $data);

        return back()->with('success', __('app.saved'));
    }

    public function reanalyze(Request $request, SupportCase $case, AnalyzeCase $action): RedirectResponse
    {
        Gate::authorize('review', $case);
        $action->handle($case);

        return back()->with('success', __('app.saved'));
    }

    public function runMatching(Request $request, SupportCase $case, RunMatching $action): RedirectResponse
    {
        Gate::authorize('assign', $case);
        $action->handle($case, $request->user()->id);

        return back()->with('success', __('app.saved'));
    }

    /** Candidate experts (scored) for manual assignment. */
    public function candidates(Request $request, SupportCase $case, MatchingEngine $engine): JsonResponse
    {
        Gate::authorize('assign', $case);
        $ranked = $engine->rank($case, 10)->map(fn ($r) => ['score' => $r['score'], 'reasons' => array_map(fn ($x) => $x[app()->getLocale()] ?? $x['en'], $r['reasons']), 'expert' => $r['expert']->toCard()]);
        $others = ExpertProfile::verified()->with(['user', 'languages', 'categories'])->whereNotIn('id', $ranked->pluck('expert.id'))->limit(20)->get()
            ->map(fn ($e) => ['score' => null, 'reasons' => [], 'expert' => $e->toCard()]);

        return response()->json(['candidates' => $ranked->concat($others)->values()]);
    }

    public function propose(Request $request, SupportCase $case, ProposeExpertManually $action): RedirectResponse
    {
        Gate::authorize('assign', $case);
        $data = $request->validate(['expert_profile_id' => ['required', 'exists:expert_profiles,id']]);
        $expert = ExpertProfile::verified()->findOrFail($data['expert_profile_id']);
        $action->handle($case, $expert, $request->user());

        return back()->with('success', __('app.saved'));
    }

    public function assignManager(Request $request, SupportCase $case): RedirectResponse
    {
        Gate::authorize('assign', $case);
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']]);
        $manager = User::findOrFail($data['user_id']);
        abort_unless($manager->isStaff(), 422);
        $case->update(['case_manager_id' => $manager->id]);
        app(CaseTimeline::class)->record($case, 'case_manager_assigned', ['name' => $manager->name]);
        if ($case->conversation) {
            app(EnsureCaseWorkspace::class)->handle($case);
        }

        return back()->with('success', __('app.saved'));
    }

    /** Claim a pending review so colleagues know someone is on it. */
    public function claim(Request $request, AiHumanReview $review): RedirectResponse
    {
        Gate::authorize('review', $review->case);
        $review->update(['reviewer_id' => $request->user()->id]);

        return redirect()->route('review.cases.show', $review->case->number);
    }
}
