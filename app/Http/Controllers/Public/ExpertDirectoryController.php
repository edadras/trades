<?php

namespace App\Http\Controllers\Public;

use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Experts\Models\ExpertProfile;
use App\Http\Controllers\Controller;
use App\Support\Seo;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpertDirectoryController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate(['category' => ['nullable', 'string', 'max:80'], 'language' => ['nullable', 'in:fa,en']]);

        $experts = ExpertProfile::verified()->with(['user', 'languages', 'categories'])
            ->when($filters['category'] ?? null, fn ($q, $slug) => $q->whereHas('categories', fn ($c) => $c->where('slug', $slug)->orWhereHas('parent', fn ($p) => $p->where('slug', $slug))))
            ->when($filters['language'] ?? null, fn ($q, $lang) => $q->whereHas('languages', fn ($l) => $l->where('language', $lang)))
            ->orderByDesc('years_experience')->paginate(12)->withQueryString()->through->toCard();

        return Inertia::render('Public/Experts', [
            'experts' => $experts,
            'categories' => CaseCategory::roots()->where('is_active', true)->orderBy('sort_order')->get()->map->toOption()->all(),
            'filters' => $filters,
            'seo' => Seo::page('experts'),
        ]);
    }
}
