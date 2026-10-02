<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Knowledge\Models\KnowledgeCategory;
use App\Domain\Knowledge\Models\KnowledgeSource;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Learning-centre categories and content sources. */
class KnowledgeTaxonomyController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Knowledge/Taxonomy', [
            'categories' => KnowledgeCategory::withCount('articles')->orderBy('sort_order')->get()->map(fn ($c) => $c->only(['id', 'slug', 'icon', 'color', 'sort_order', 'articles_count']) + ['name' => $c->translations('name')]),
            'sources' => KnowledgeSource::orderBy('name')->get(['id', 'name', 'publisher', 'url', 'type', 'reliability']),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        KnowledgeCategory::create($this->category($request));

        return back()->with('success', __('app.saved'));
    }

    public function updateCategory(Request $request, KnowledgeCategory $category): RedirectResponse
    {
        $category->update($this->category($request, $category));

        return back()->with('success', __('app.saved'));
    }

    public function storeSource(Request $request): RedirectResponse
    {
        KnowledgeSource::create($this->source($request));

        return back()->with('success', __('app.saved'));
    }

    public function updateSource(Request $request, KnowledgeSource $source): RedirectResponse
    {
        $source->update($this->source($request));

        return back()->with('success', __('app.saved'));
    }

    private function category(Request $request, ?KnowledgeCategory $category = null): array
    {
        return $request->validate([
            'slug' => ['required', 'alpha_dash', 'max:80', Rule::unique('knowledge_categories', 'slug')->ignore($category?->id)],
            'name.fa' => ['required', 'string', 'max:120'], 'name.en' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:40'], 'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sort_order' => ['integer', 'min:0'],
        ]);
    }

    private function source(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'], 'publisher' => ['nullable', 'string', 'max:150'], 'url' => ['nullable', 'url', 'max:300'],
            'type' => ['required', Rule::in(['internal', 'government', 'academic', 'industry', 'international', 'other'])],
            'reliability' => ['required', Rule::in(['high', 'medium', 'low'])],
        ]);
    }
}
