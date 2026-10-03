<?php

namespace App\Http\Controllers\Business;

use App\Domain\Business\Actions\SaveOnboardingStep;
use App\Domain\Business\Models\BusinessDocument;
use App\Domain\Pilot\Actions\EvaluateEligibility;
use App\Http\Controllers\Controller;
use App\Models\PrivacySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BusinessProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        $business = $request->user()->currentBusiness();
        abort_unless($business, 403);

        return Inertia::render('Business/Profile', ['business' => OnboardingController::payload($business), 'canEdit' => $business->canManageTeam($request->user())]);
    }

    public function update(Request $request, EvaluateEligibility $eligibility): RedirectResponse
    {
        $business = $request->user()->currentBusiness();
        abort_unless($business && $business->canManageTeam($request->user()), 403);

        $rules = collect(range(1, 7))->flatMap(fn ($s) => SaveOnboardingStep::rules($s))->all();
        $data = Validator::make($request->all(), $rules)->validate();
        $business->fill($data);
        $scopeChanged = $business->isDirty(['country', 'province', 'industry', 'size']);
        $business->save();
        if ($scopeChanged && $business->isOnboarded() && ! EvaluateEligibility::isOverridden($business)) {
            $eligibility->handle($business);
        }

        if ($request->has('privacy')) {
            $privacy = $request->validate(['privacy' => ['array'], 'privacy.*' => [Rule::in(PrivacySetting::LEVELS)]])['privacy'];
            $business->syncPrivacy($privacy);
        }

        return back()->with('success', __('app.saved'));
    }

    public function uploadDocument(Request $request, SaveOnboardingStep $action): RedirectResponse
    {
        $business = $request->user()->currentBusiness();
        abort_unless($business && $business->canManageTeam($request->user()), 403);
        $action->handle($business, $request->user(), 8, $request->all());

        return back()->with('success', __('app.saved'));
    }

    public function destroyDocument(Request $request, BusinessDocument $document): RedirectResponse
    {
        abort_unless($document->business->canManageTeam($request->user()), 403);
        $document->delete();

        return back()->with('success', __('app.deleted'));
    }
}
