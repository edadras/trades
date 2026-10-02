<?php

namespace Database\Seeders;

use App\Domain\Cases\Models\CaseCategory;
use Illuminate\Database\Seeder;

class TaxonomySeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('taxonomy') as $i => $root) {
            $parent = CaseCategory::updateOrCreate(['slug' => $root['slug']], [
                'name' => $root['name'],
                'description' => $root['description'] ?? null,
                'keywords' => $root['keywords'],
                'icon' => $root['icon'] ?? null,
                'is_sensitive' => $root['is_sensitive'] ?? false,
                'default_urgency' => $root['default_urgency'] ?? 'medium',
                'sort_order' => $i * 10,
            ]);
            foreach ($root['children'] ?? [] as $j => $child) {
                CaseCategory::updateOrCreate(['slug' => $child['slug']], [
                    'parent_id' => $parent->id,
                    'name' => $child['name'],
                    'keywords' => $child['keywords'],
                    'is_sensitive' => $child['is_sensitive'] ?? ($root['is_sensitive'] ?? false),
                    'default_urgency' => $child['default_urgency'] ?? ($root['default_urgency'] ?? 'medium'),
                    'sort_order' => $i * 10 + $j + 1,
                ]);
            }
        }
    }
}
