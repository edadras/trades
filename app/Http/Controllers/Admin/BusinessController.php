<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Business\Models\Business;
use App\Domain\Identity\AuditLogger;
use App\Http\Controllers\Business\OnboardingController;
use App\Http\Controllers\Controller;
use App\Http\Presenters\CasePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BusinessController extends Controller
{
    public function index(Request $request): Response
    {
        $q = $request->validate(['q' => ['nullable', 'string', 'max:80']])['q'] ?? null;
        $businesses = Business::withCount('cases')->with('owner:id,name,email')
            ->when($q, fn ($query) => $query->where('trade_name', 'like', "%{$q}%"))
            ->latest()->paginate(25)->withQueryString()
            ->through(fn (Business $b) => [
                'id' => $b->id, 'name' => $b->trade_name, 'industry' => $b->industry, 'size' => $b->size, 'country' => $b->country, 'province' => $b->province,
                'owner' => $b->owner?->only(['name', 'email']), 'cases' => $b->cases_count, 'onboarded' => $b->isOnboarded(), 'created_at' => $b->created_at->toIso8601String(),
            ]);

        return Inertia::render('Admin/Businesses', ['businesses' => $businesses, 'filters' => ['q' => $q]]);
    }

    public function show(Business $business, CasePresenter $presenter, AuditLogger $audit): Response
    {
        $audit->log('business.viewed', $business);

        return Inertia::render('Admin/BusinessShow', [
            'business' => OnboardingController::payload($business) + ['owner' => $business->owner->only(['name', 'email'])],
            'cases' => $business->cases()->with(['category', 'subcategory'])->latest()->get()->map(fn ($c) => $presenter->card($c)),
        ]);
    }
}
