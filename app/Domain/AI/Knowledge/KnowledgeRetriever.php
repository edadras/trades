<?php

namespace App\Domain\AI\Knowledge;

use App\Domain\AI\Data\Classification;
use App\Domain\AI\Support\TextNormalizer;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use Illuminate\Support\Collection;

/**
 * Retrieval step of "Approved Knowledge Base → Retrieval → AI". Only approved, published and
 * still-valid content is ever returned, so the AI can never ground answers on unapproved text.
 */
class KnowledgeRetriever
{
    /** @return Collection<int, array{article: KnowledgeArticle, score: float}> */
    public function retrieve(string $text, Classification $classification, ?string $industry = null, ?string $country = null, ?int $limit = null): Collection
    {
        $limit ??= config('ai.knowledge.max_results');
        $categoryIds = array_filter([$classification->category?->id, $classification->subcategory?->id]);
        $queryTokens = array_unique(TextNormalizer::tokens($text));

        $articles = KnowledgeArticle::query()->approved()
            ->with(['translations', 'problemTypes:id', 'category'])
            ->get();

        return $articles->map(function (KnowledgeArticle $article) use ($classification, $categoryIds, $queryTokens, $industry, $country) {
            $score = 0.0;
            $problemIds = $article->problemTypes->pluck('id')->all();
            if ($classification->subcategory && in_array($classification->subcategory->id, $problemIds, true)) {
                $score += 0.5;
            }
            if ($classification->category && in_array($classification->category->id, $problemIds, true)) {
                $score += 0.35;
            }
            if ($industry && in_array($industry, $article->industries ?? [], true)) {
                $score += 0.1;
            }
            if ($country && $article->country === $country) {
                $score += 0.05;
            }
            $haystack = array_unique(TextNormalizer::tokens($article->translations->map(fn ($t) => $t->title.' '.$t->summary)->implode(' ')));
            $overlap = count(array_intersect($queryTokens, $haystack));
            $score += min(0.3, $overlap * 0.05);

            return ['article' => $article, 'score' => round($score, 3), 'categoryMatch' => (bool) array_intersect($categoryIds, $problemIds)];
        })
            ->filter(fn ($r) => $r['score'] >= config('ai.knowledge.min_score'))
            ->sortByDesc('score')
            ->take($limit)
            ->map(fn ($r) => ['article' => $r['article'], 'score' => $r['score']])
            ->values();
    }
}
