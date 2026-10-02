<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Business\Models\Partner;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PartnerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Partners', [
            'partners' => Partner::withCount(['businesses', 'cases'])->latest()->get()->map(fn (Partner $p) => $p->only(['id', 'type', 'referral_code', 'contact_name', 'contact_email', 'referral_method', 'is_active', 'businesses_count', 'cases_count']) + [
                'name' => $p->translations('name'),
                'referral_url' => route('register', ['locale' => app()->getLocale(), 'ref' => $p->referral_code]),
            ]),
            'types' => Partner::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Partner::create($this->validated($request));

        return back()->with('success', __('app.saved'));
    }

    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $partner->update($this->validated($request, $partner));

        return back()->with('success', __('app.saved'));
    }

    private function validated(Request $request, ?Partner $partner = null): array
    {
        return $request->validate([
            'name.fa' => ['required', 'string', 'max:150'], 'name.en' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(Partner::TYPES)],
            'referral_code' => ['nullable', 'alpha_num', 'max:40', Rule::unique('partners', 'referral_code')->ignore($partner?->id)],
            'contact_name' => ['nullable', 'string', 'max:120'], 'contact_email' => ['nullable', 'email', 'max:150'],
            'referral_method' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
        ]);
    }
}
