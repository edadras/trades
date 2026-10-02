<?php

namespace App\Http\Controllers\Public;

use App\Domain\Analytics\MetricCalculator;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Domain\Knowledge\Models\KnowledgeCategory;
use App\Http\Controllers\Controller;
use App\Support\Seo;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(MetricCalculator $metrics): Response
    {
        $locale = app()->getLocale();
        $data = Cache::remember("home:{$locale}", 300, fn () => [
            'categories' => CaseCategory::roots()->where('is_active', true)->where('slug', '!=', 'general')->orderBy('sort_order')->get()
                ->map(fn ($c) => $c->toOption() + ['description' => $c->translate('description')])->all(),
            'experts' => ExpertProfile::verified()->with(['user', 'languages', 'categories'])->inRandomOrder()->limit(6)->get()->map->toCard()->all(),
            'articles' => KnowledgeArticle::approved()->with(['translations', 'category'])->latest('published_at')->limit(3)->get()->map->toCard()->all(),
            'knowledge_categories' => KnowledgeCategory::orderBy('sort_order')->get()->map->toOption()->all(),
            'live' => [
                'businesses' => (int) $metrics->value('active_businesses'),
                'experts' => (int) $metrics->value('verified_supporters'),
                'cases' => (int) $metrics->value('real_cases'),
                'review_hours' => $metrics->initialReviewHours(),
            ],
            'stories' => SupportCase::query()->whereHas('outcome', fn ($q) => $q->whereIn('outcome', ['resolved', 'partially_resolved'])->where('confirmation_status', 'confirmed'))
                // Only cases whose business agreed to anonymised use for learning are published as stories.
                ->where('data_consent->anonymized_learning', true)
                ->whereHas('surveys', fn ($q) => $q->where('rating', '>=', 4)->whereNotNull('comment'))
                ->with(['category', 'business', 'surveys', 'outcome'])->latest('closed_at')->limit(6)->get()
                ->map(fn ($c) => [
                    'category' => $c->category?->translate('name'),
                    'industry' => $c->business->industry,
                    'rating' => $c->surveys->max('rating'),
                    'quote' => $c->surveys->firstWhere('rating', '>=', 4)?->comment,
                    'result' => $c->outcome?->result_summary,
                ])->all(),
        ]);

        return Inertia::render('Public/Home', $data + ['seo' => Seo::page('home')]);
    }
}
