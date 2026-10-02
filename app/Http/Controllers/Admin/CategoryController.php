<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Cases\Enums\Urgency;
use App\Domain\Cases\Models\CaseCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Problem taxonomy, including the keywords the offline classifier uses. */
class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories', [
            'categories' => CaseCategory::orderBy('sort_order')->get()->map(fn ($c) => [
                'id' => $c->id, 'parent_id' => $c->parent_id, 'slug' => $c->slug, 'name' => $c->translations('name'),
                'description' => $c->translations('description'), 'keywords' => $c->keywords ?? ['fa' => [], 'en' => []],
                'icon' => $c->icon, 'is_sensitive' => $c->is_sensitive, 'default_urgency' => $c->default_urgency,
                'sort_order' => $c->sort_order, 'is_active' => $c->is_active,
            ]),
            'urgencies' => Urgency::values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        CaseCategory::create($this->validated($request));

        return back()->with('success', __('app.saved'));
    }

    public function update(Request $request, CaseCategory $category): RedirectResponse
    {
        $category->update($this->validated($request, $category));

        return back()->with('success', __('app.saved'));
    }

    private function validated(Request $request, ?CaseCategory $category = null): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:case_categories,id', Rule::notIn([$category?->id])],
            'slug' => ['required', 'alpha_dash', 'max:80', Rule::unique('case_categories', 'slug')->ignore($category?->id)],
            'name.fa' => ['required', 'string', 'max:120'], 'name.en' => ['required', 'string', 'max:120'],
            'description.fa' => ['nullable', 'string', 'max:500'], 'description.en' => ['nullable', 'string', 'max:500'],
            'keywords.fa' => ['array'], 'keywords.fa.*' => ['string', 'max:60'],
            'keywords.en' => ['array'], 'keywords.en.*' => ['string', 'max:60'],
            'icon' => ['nullable', 'string', 'max:40'],
            'is_sensitive' => ['boolean'],
            'default_urgency' => ['required', Rule::in(Urgency::values())],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
        $data['keywords'] = ['fa' => $data['keywords']['fa'] ?? [], 'en' => $data['keywords']['en'] ?? []];

        return $data;
    }
}
