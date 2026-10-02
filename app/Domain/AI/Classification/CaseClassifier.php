<?php

namespace App\Domain\AI\Classification;

use App\Domain\AI\AIManager;
use App\Domain\AI\Data\Classification;
use App\Domain\AI\Providers\AIProviderException;
use App\Domain\AI\Safety\PiiRedactor;
use App\Domain\AI\Support\TextNormalizer;
use App\Domain\Cases\Enums\Urgency;
use App\Domain\Cases\Models\CaseCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CaseClassifier
{
    /** @var array<string, array<int, string>> */
    private const URGENCY_SIGNALS = [
        'critical' => ['فوری', 'همین امروز', 'تعطیل شده', 'توقف کامل', 'ورشکست', 'emergency', 'shut down', 'stopped completely', 'immediately', 'bankrupt'],
        'high' => ['هفته آینده', 'مهلت', 'ضرر', 'زیان', 'افت شدید', 'deadline', 'urgent', 'asap', 'losing', 'loss', 'sharp drop', 'this week'],
        'low' => ['در آینده', 'برنامه ریزی', 'ایده', 'کنجکاو', 'someday', 'planning', 'curious', 'idea', 'long term', 'بلند مدت'],
    ];

    public function __construct(private readonly AIManager $ai, private readonly PiiRedactor $redactor) {}

    public function classify(string $text, ?string $industry = null): Classification
    {
        $categories = CaseCategory::query()->where('is_active', true)->get();

        if ($this->ai->usesLanguageModel()) {
            try {
                return $this->classifyWithModel($text, $categories);
            } catch (AIProviderException $e) {
                Log::warning('AI classification fell back to heuristics', ['error' => $e->getMessage()]);
            }
        }

        return $this->classifyHeuristically($text, $categories);
    }

    /** @param Collection<int, CaseCategory> $categories */
    public function classifyHeuristically(string $text, Collection $categories): Classification
    {
        $normalized = TextNormalizer::normalize($text);
        $scores = [];
        foreach ($categories as $category) {
            $hits = 0.0;
            foreach (array_merge(...array_values($category->keywords ?? [[]])) as $keyword) {
                if (TextNormalizer::contains($normalized, $keyword)) {
                    $hits += str_contains(trim($keyword), ' ') ? 1.5 : 1.0;
                }
            }
            $scores[$category->id] = $hits;
        }

        $roots = $categories->whereNull('parent_id');
        $rootScores = [];
        foreach ($roots as $root) {
            $childBest = $categories->where('parent_id', $root->id)->map(fn ($c) => $scores[$c->id] ?? 0)->max() ?? 0;
            $rootScores[$root->id] = ($scores[$root->id] ?? 0) + $childBest;
        }
        arsort($rootScores);
        $ranked = array_values($rootScores);
        $best = $ranked[0] ?? 0;
        $second = $ranked[1] ?? 0;

        if ($best <= 0) {
            $fallback = $categories->firstWhere('slug', 'general');

            return new Classification($fallback, null, $this->urgency($normalized, Urgency::Medium), 0.2);
        }

        $category = $categories->firstWhere('id', array_key_first($rootScores));
        $subcategory = $categories->where('parent_id', $category->id)
            ->sortByDesc(fn ($c) => $scores[$c->id] ?? 0)->first();
        if ($subcategory && ($scores[$subcategory->id] ?? 0) <= 0) {
            $subcategory = null;
        }

        $dominance = $best / ($best + $second);
        $confidence = min(0.97, 0.45 + 0.12 * $best) * $dominance;
        $default = Urgency::tryFrom($subcategory?->default_urgency ?? $category->default_urgency) ?? Urgency::Medium;

        return new Classification(
            $category,
            $subcategory,
            $this->urgency($normalized, $default),
            round($confidence, 3),
            array_values(array_filter([$category->slug, $subcategory?->slug])),
        );
    }

    private function urgency(string $normalized, Urgency $default): Urgency
    {
        foreach (['critical', 'high', 'low'] as $level) {
            foreach (self::URGENCY_SIGNALS[$level] as $needle) {
                if (TextNormalizer::contains($normalized, $needle)) {
                    $signal = Urgency::from($level);

                    return $level === 'low' && $default->weight() > Urgency::Medium->weight() ? $default : $signal;
                }
            }
        }

        return $default;
    }

    /** @param Collection<int, CaseCategory> $categories */
    private function classifyWithModel(string $text, Collection $categories): Classification
    {
        $taxonomy = $categories->whereNull('parent_id')->map(fn ($root) => [
            'slug' => $root->slug,
            'name' => $root->translate('name', 'en'),
            'subcategories' => $categories->where('parent_id', $root->id)->map(fn ($c) => ['slug' => $c->slug, 'name' => $c->translate('name', 'en')])->values()->all(),
        ])->values()->all();

        $system = 'You classify problems reported by small and medium businesses. Use ONLY the taxonomy provided. '
            .'Return JSON: {"category": slug, "subcategory": slug|null, "urgency": "low|medium|high|critical", '
            .'"confidence": number 0..1 (be conservative; below 0.75 means a human must check), "required_expertises": [slug]}. '
            .'Taxonomy: '.json_encode($taxonomy, JSON_UNESCAPED_UNICODE);

        $json = $this->ai->provider()->generateJson($system, [['role' => 'user', 'content' => $this->redactor->redact($text)]], 0.0);

        $category = $categories->whereNull('parent_id')->firstWhere('slug', $json['category'] ?? null);
        if (! $category) {
            throw new AIProviderException('Model returned an unknown category.');
        }
        $subcategory = $categories->where('parent_id', $category->id)->firstWhere('slug', $json['subcategory'] ?? null);
        $slugs = $categories->pluck('slug')->all();

        return new Classification(
            $category,
            $subcategory,
            Urgency::tryFrom((string) ($json['urgency'] ?? '')) ?? Urgency::tryFrom($category->default_urgency) ?? Urgency::Medium,
            max(0.0, min(1.0, (float) ($json['confidence'] ?? 0.5))),
            array_values(array_intersect((array) ($json['required_expertises'] ?? []), $slugs)) ?: array_values(array_filter([$category->slug, $subcategory?->slug])),
        );
    }
}
