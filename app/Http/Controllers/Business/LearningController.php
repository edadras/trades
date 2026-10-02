<?php

namespace App\Http\Controllers\Business;

use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** "Recommended for your business": content linked to the business's cases and industry. */
class LearningController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $business = $request->user()->currentBusiness();
        $cases = $business->cases()->with(['recommendedContents.translations', 'recommendedContents.category'])->real()->latest()->get();

        $fromCases = $cases->flatMap(fn ($c) => $c->recommendedContents->filter->isUsableByAi()->map(fn ($a) => $a->toCard() + [
            'case_number' => $c->number, 'relevance' => (float) $a->pivot->relevance, 'viewed' => (bool) $a->pivot->viewed_at,
        ]))->unique('id')->values();

        $byIndustry = KnowledgeArticle::approved()->whereJsonContains('industries', $business->industry ?? '__none__')
            ->whereNotIn('id', $fromCases->pluck('id'))->with(['translations', 'category'])->limit(6)->get()->map->toCard();

        return Inertia::render('Business/Learning', ['fromCases' => $fromCases, 'byIndustry' => $byIndustry]);
    }
}
