<?php

namespace App\Http\Controllers\Business;

use App\Domain\Business\Actions\SaveOnboardingStep;
use App\Domain\Business\Models\Business;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        $business = $request->user()->currentBusiness();
        abort_unless($business, 403);

        return Inertia::render('Business/Onboarding', [
            'business' => $this->payload($business),
            'step' => (int) $request->query('step', $business->onboarding_step),
            'steps' => Business::ONBOARDING_STEPS,
        ]);
    }

    public function store(Request $request, SaveOnboardingStep $action): RedirectResponse
    {
        $business = $request->user()->currentBusiness();
        abort_unless($business && $business->owner_id === $request->user()->id, 403);
        $step = (int) $request->validate(['step' => ['required', 'integer', Rule::in(range(1, Business::ONBOARDING_STEPS))]])['step'];

        $action->handle($business, $request->user(), $step, $request->all());

        if ($step === Business::ONBOARDING_STEPS) {
            return redirect()->route('dashboard')->with('success', __('app.saved'));
        }

        return redirect()->route('onboarding.show', ['step' => $step + 1]);
    }

    public static function payload(Business $business): array
    {
        return $business->only([
            'id', 'trade_name', 'legal_name', 'founded_year', 'website', 'description', 'products_services', 'industry', 'size',
            'employees_range', 'country', 'province', 'city', 'preferred_language', 'main_needs', 'onboarding_step', 'logo_path',
        ]) + [
            'registration_number' => $business->registration_number,
            'contact_name' => $business->contact_name,
            'contact_email' => $business->contact_email,
            'contact_phone' => $business->contact_phone,
            'address' => $business->address,
            'privacy' => $business->privacyMap(),
            'documents' => $business->documents()->latest()->get()->map(fn ($d) => $d->fileSummary() + ['type' => $d->type])->all(),
            'onboarded' => $business->isOnboarded(),
        ];
    }
}
