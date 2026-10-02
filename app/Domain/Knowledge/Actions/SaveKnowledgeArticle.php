<?php

namespace App\Domain\Knowledge\Actions;

use App\Domain\Knowledge\Enums\ContentStatus;
use App\Domain\Knowledge\Enums\ContentType;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Domain\Knowledge\Models\KnowledgeTag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveKnowledgeArticle
{
    /** @return array<string, mixed> */
    public static function rules(?KnowledgeArticle $article = null): array
    {
        return [
            'slug' => ['nullable', 'string', 'max:160', 'alpha_dash', Rule::unique('knowledge_articles', 'slug')->ignore($article?->id)],
            'type' => ['required', Rule::in(ContentType::values())],
            'knowledge_category_id' => ['required', 'exists:knowledge_categories,id'],
            'knowledge_source_id' => ['nullable', 'exists:knowledge_sources,id'],
            'country' => ['nullable', Rule::in(config('platform.countries'))],
            'industries' => ['array'], 'industries.*' => [Rule::in(config('platform.industries'))],
            'problem_types' => ['array'], 'problem_types.*' => ['exists:case_categories,id'],
            'tags' => ['array'], 'tags.*' => ['string', 'max:40'],
            'cover_image' => ['nullable', 'string', 'max:300'],
            'video_url' => ['nullable', 'url', 'max:300'],
            'reading_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'valid_until' => ['nullable', 'date'],
            'is_featured' => ['boolean'],
            'translations' => ['required', 'array'],
            'translations.fa.title' => ['required_without:translations.en.title', 'nullable', 'string', 'max:200'],
            'translations.en.title' => ['required_without:translations.fa.title', 'nullable', 'string', 'max:200'],
            'translations.*.summary' => ['nullable', 'string', 'max:600'],
            'translations.*.body' => ['nullable', 'string', 'max:100000'],
            'translations.*.checklist' => ['nullable', 'array'],
            'translations.*.checklist.*' => ['nullable', 'array'],
            'translations.*.checklist.*.*' => ['string', 'max:300'],
            'translations.*.seo_title' => ['nullable', 'string', 'max:200'],
            'translations.*.seo_description' => ['nullable', 'string', 'max:300'],
        ];
    }

    /** @param array<string, mixed> $data */
    public function handle(?KnowledgeArticle $article, User $author, array $data): KnowledgeArticle
    {
        return DB::transaction(function () use ($article, $author, $data) {
            $article ??= new KnowledgeArticle(['author_id' => $author->id, 'verification_status' => ContentStatus::Draft]);
            $title = $data['translations']['en']['title'] ?? $data['translations']['fa']['title'];
            $article->fill(collect($data)->except(['translations', 'tags', 'problem_types'])->all());
            $article->slug = $data['slug'] ?? $article->slug ?? (Str::slug($title) ?: 'content').'-'.Str::lower(Str::random(5));
            // Any edit to approved content sends it back for re-approval so the AI never uses unreviewed text.
            if ($article->exists && $article->verification_status === ContentStatus::Approved && $article->isDirty()) {
                $article->verification_status = ContentStatus::InReview;
            }
            $article->save();

            foreach ($data['translations'] as $locale => $t) {
                if (! in_array($locale, config('platform.locales'), true) || blank($t['title'] ?? null)) {
                    continue;
                }
                $article->translations()->updateOrCreate(['locale' => $locale], [
                    'title' => $t['title'], 'summary' => $t['summary'] ?? null, 'body' => $t['body'] ?? '',
                    'checklist' => $t['checklist'] ?? null, 'seo_title' => $t['seo_title'] ?? null, 'seo_description' => $t['seo_description'] ?? null,
                ]);
            }

            $tagIds = collect($data['tags'] ?? [])->map(fn ($name) => KnowledgeTag::firstOrCreate(['slug' => Str::slug($name) ?: md5($name)], ['name' => ['fa' => $name, 'en' => $name]])->id);
            $article->tags()->sync($tagIds);
            $article->problemTypes()->sync($data['problem_types'] ?? []);

            return $article->fresh(['translations']);
        });
    }
}
