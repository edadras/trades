<?php

namespace Database\Seeders;

use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Knowledge\Enums\ContentStatus;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Domain\Knowledge\Models\KnowledgeCategory;
use App\Domain\Knowledge\Models\KnowledgeSource;
use App\Domain\Knowledge\Models\KnowledgeTag;
use Illuminate\Database\Seeder;

class KnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['finance', ['fa' => 'مالی', 'en' => 'Finance'], 'wallet', '#1d4ed8'],
            ['production', ['fa' => 'تولید', 'en' => 'Production'], 'factory', '#0f766e'],
            ['export', ['fa' => 'صادرات', 'en' => 'Export'], 'globe', '#7c3aed'],
            ['energy', ['fa' => 'انرژی', 'en' => 'Energy'], 'bolt', '#d97706'],
            ['technology', ['fa' => 'فناوری', 'en' => 'Technology'], 'cpu', '#0891b2'],
            ['legal', ['fa' => 'حقوقی', 'en' => 'Legal'], 'scale', '#be123c'],
            ['hr', ['fa' => 'منابع انسانی', 'en' => 'Human resources'], 'users', '#4f46e5'],
            ['marketing', ['fa' => 'بازاریابی', 'en' => 'Marketing'], 'megaphone', '#db2777'],
            ['investment', ['fa' => 'سرمایه‌گذاری', 'en' => 'Investment'], 'trending-up', '#15803d'],
        ];
        foreach ($categories as $i => [$slug, $name, $icon, $color]) {
            KnowledgeCategory::updateOrCreate(['slug' => $slug], ['name' => $name, 'icon' => $icon, 'color' => $color, 'sort_order' => $i]);
        }

        $source = KnowledgeSource::firstOrCreate(['name' => 'Hamyar editorial team'], ['publisher' => 'Hamyar', 'type' => 'internal', 'reliability' => 'high']);

        foreach (require __DIR__.'/data/knowledge.php' as $item) {
            $article = KnowledgeArticle::withTrashed()->updateOrCreate(['slug' => $item['slug']], [
                'type' => $item['type'],
                'knowledge_category_id' => KnowledgeCategory::where('slug', $item['category'])->value('id'),
                'knowledge_source_id' => $source->id,
                'country' => 'IR',
                'industries' => $item['industries'],
                'cover_image' => $item['cover'] ?? null,
                'reading_minutes' => $item['minutes'],
                'verification_status' => ContentStatus::Approved,
                'approved_at' => now(),
                'published_at' => now()->subDays(random_int(1, 60)),
                'valid_until' => now()->addYear()->toDateString(),
                'is_featured' => $item['featured'] ?? false,
                'deleted_at' => null,
            ]);
            foreach (['fa', 'en'] as $locale) {
                $article->translations()->updateOrCreate(['locale' => $locale], $item[$locale] + ['seo_description' => $item[$locale]['summary']]);
            }
            $article->problemTypes()->sync(CaseCategory::whereIn('slug', $item['problems'])->pluck('id'));
            $tag = KnowledgeTag::firstOrCreate(['slug' => $item['category']], ['name' => collect($categories)->firstWhere(0, $item['category'])[1]]);
            $article->tags()->syncWithoutDetaching([$tag->id]);
        }
    }
}
