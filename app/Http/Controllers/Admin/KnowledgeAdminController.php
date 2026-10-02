<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Knowledge\Actions\ChangeContentStatus;
use App\Domain\Knowledge\Actions\SaveKnowledgeArticle;
use App\Domain\Knowledge\Enums\ContentStatus;
use App\Domain\Knowledge\Enums\ContentType;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Domain\Knowledge\Models\KnowledgeCategory;
use App\Domain\Knowledge\Models\KnowledgeSource;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['status' => ['nullable', Rule::in(ContentStatus::values())], 'q' => ['nullable', 'string', 'max:80']]);
        $articles = KnowledgeArticle::with(['translations', 'category', 'author:id,name', 'approver:id,name'])
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('verification_status', $s))
            ->when($filters['q'] ?? null, fn ($q, $t) => $q->whereHas('translations', fn ($w) => $w->where('title', 'like', "%{$t}%")))
            ->latest('updated_at')->paginate(20)->withQueryString()
            ->through(fn ($a) => $a->toCard() + [
                'status' => $a->verification_status->value,
                'locales' => $a->translations->pluck('locale')->all(),
                'author' => $a->author?->name, 'approver' => $a->approver?->name,
                'valid_until' => $a->valid_until?->toDateString(), 'expired' => $a->valid_until?->isPast() ?? false,
                'updated_at' => $a->updated_at->toIso8601String(), 'views' => $a->views,
            ]);

        return Inertia::render('Admin/Knowledge/Index', ['articles' => $articles, 'filters' => $filters, 'statuses' => ContentStatus::values()]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Knowledge/Edit', $this->formData(null));
    }

    public function store(Request $request, SaveKnowledgeArticle $action): RedirectResponse
    {
        $article = $action->handle(null, $request->user(), $request->validate(SaveKnowledgeArticle::rules()));

        return redirect()->route('admin.knowledge.edit', $article->id)->with('success', __('knowledge.saved'));
    }

    public function edit(KnowledgeArticle $article): Response
    {
        return Inertia::render('Admin/Knowledge/Edit', $this->formData($article));
    }

    public function update(Request $request, KnowledgeArticle $article, SaveKnowledgeArticle $action): RedirectResponse
    {
        $action->handle($article, $request->user(), $request->validate(SaveKnowledgeArticle::rules($article)));

        return back()->with('success', __('knowledge.saved'));
    }

    public function status(Request $request, KnowledgeArticle $article, ChangeContentStatus $action): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(ContentStatus::values())]]);
        $action->handle($article, $request->user(), ContentStatus::from($data['status']));

        return back()->with('success', __('knowledge.saved'));
    }

    private function formData(?KnowledgeArticle $article): array
    {
        $article?->load(['translations', 'tags', 'problemTypes']);

        return [
            'article' => $article ? $article->only(['id', 'slug', 'knowledge_category_id', 'knowledge_source_id', 'country', 'industries', 'cover_image', 'video_url', 'reading_minutes', 'is_featured']) + [
                'type' => $article->type->value,
                'status' => $article->verification_status->value,
                'valid_until' => $article->valid_until?->toDateString(),
                'tags' => $article->tags->map(fn ($t) => $t->translate('name'))->all(),
                'problem_types' => $article->problemTypes->pluck('id')->all(),
                'translations' => collect(config('platform.locales'))->mapWithKeys(function ($l) use ($article) {
                    $t = $article->translations->firstWhere('locale', $l);

                    return [$l => [
                        'title' => $t?->title, 'summary' => $t?->summary, 'body' => $t?->body,
                        'checklist' => array_merge(['causes' => [], 'actions' => [], 'documents' => [], 'warnings' => []], $t?->checklist ?? []),
                        'seo_title' => $t?->seo_title, 'seo_description' => $t?->seo_description,
                    ]];
                })->all(),
            ] : null,
            'categories' => KnowledgeCategory::orderBy('sort_order')->get()->map->toOption(),
            'sources' => KnowledgeSource::orderBy('name')->get(['id', 'name', 'publisher']),
            'problemTypes' => CaseCategory::where('is_active', true)->orderBy('sort_order')->get()->map->toOption(),
            'types' => ContentType::values(),
            'canApprove' => request()->user()->can('knowledge.approve'),
        ];
    }
}
