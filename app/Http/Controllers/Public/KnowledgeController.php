<?php

namespace App\Http\Controllers\Public;

use App\Domain\Knowledge\Enums\ContentType;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Domain\Knowledge\Models\KnowledgeCategory;
use App\Http\Controllers\Controller;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class KnowledgeController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:80'],
            'type' => ['nullable', 'in:'.implode(',', ContentType::values())],
        ]);

        $query = KnowledgeArticle::approved()->with(['translations', 'category']);
        if (! empty($filters['q'])) {
            $ids = KnowledgeArticle::search($filters['q'])->take(100)->keys();
            $query->whereIn('id', $ids);
        }
        if (! empty($filters['category'])) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $filters['category']));
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $articles = $query->orderByDesc('is_featured')->latest('published_at')->paginate(12)->withQueryString()
            ->through(fn ($a) => $a->toCard());

        return Inertia::render('Public/Knowledge/Index', [
            'articles' => $articles,
            'categories' => KnowledgeCategory::orderBy('sort_order')->withCount(['articles' => fn ($q) => $q->approved()])->get()
                ->map(fn ($c) => $c->toOption() + ['count' => $c->articles_count])->all(),
            'types' => ContentType::values(),
            'filters' => $filters,
            'seo' => Seo::page('knowledge'),
        ]);
    }

    public function show(KnowledgeArticle $article): Response
    {
        abort_unless($article->isUsableByAi(), 404);
        $article->load(['translations', 'category', 'source', 'tags', 'problemTypes']);
        Cache::lock("view:{$article->id}:".request()->ip(), 3600)->get(fn () => $article->increment('views'));
        $t = $article->translation();

        return Inertia::render('Public/Knowledge/Show', [
            'article' => $article->toCard() + [
                'body' => $t?->body,
                'checklist' => $t?->checklist,
                'locale' => $t?->locale,
                'video_url' => $article->video_url,
                'valid_until' => $article->valid_until?->toDateString(),
                'source' => $article->source?->only(['name', 'publisher', 'url']),
                'tags' => $article->tags->map(fn ($tag) => $tag->translate('name'))->all(),
                'problem_types' => $article->problemTypes->map(fn ($c) => $c->translate('name'))->all(),
                'translations' => $article->translations->pluck('locale')->all(),
                'updated_at' => $article->updated_at->toDateString(),
            ],
            'related' => KnowledgeArticle::approved()->where('id', '!=', $article->id)
                ->where('knowledge_category_id', $article->knowledge_category_id)->with(['translations', 'category'])->limit(3)->get()->map->toCard()->all(),
            'seo' => Seo::forArticle($article),
        ]);
    }
}
