<?php

namespace App\Domain\Knowledge\Models;

use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Knowledge\Enums\ContentStatus;
use App\Domain\Knowledge\Enums\ContentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class KnowledgeArticle extends Model
{
    use Searchable, SoftDeletes;

    protected $fillable = [
        'slug', 'type', 'knowledge_category_id', 'knowledge_source_id', 'author_id', 'approved_by', 'country',
        'industries', 'cover_image', 'video_url', 'attachment_path', 'reading_minutes', 'verification_status',
        'valid_until', 'approved_at', 'published_at', 'views', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'type' => ContentType::class,
            'verification_status' => ContentStatus::class,
            'industries' => 'array',
            'valid_until' => 'date',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function translations(): HasMany
    {
        return $this->hasMany(KnowledgeTranslation::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'knowledge_category_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSource::class, 'knowledge_source_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeTag::class, 'knowledge_article_tag');
    }

    public function problemTypes(): BelongsToMany
    {
        return $this->belongsToMany(CaseCategory::class, 'knowledge_article_case_category');
    }

    /** Only approved, published, still-valid content may be shown publicly or used by the AI. */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('verification_status', ContentStatus::Approved->value)
            ->whereNotNull('published_at')->where('published_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()->toDateString()));
    }

    public function translation(?string $locale = null): ?KnowledgeTranslation
    {
        $locale ??= app()->getLocale();
        $translations = $this->relationLoaded('translations') ? $this->translations : $this->translations()->get();

        return $translations->firstWhere('locale', $locale) ?? $translations->firstWhere('locale', config('app.fallback_locale')) ?? $translations->first();
    }

    public function isUsableByAi(): bool
    {
        return $this->verification_status === ContentStatus::Approved
            && $this->published_at?->isPast()
            && (is_null($this->valid_until) || ! $this->valid_until->isPast());
    }

    public function toCard(?string $locale = null): array
    {
        $t = $this->translation($locale);

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'title' => $t?->title,
            'summary' => $t?->summary,
            'cover_image' => $this->cover_image,
            'reading_minutes' => $this->reading_minutes,
            'published_at' => $this->published_at?->toDateString(),
            'category' => $this->category ? ['slug' => $this->category->slug, 'name' => $this->category->translate('name'), 'color' => $this->category->color] : null,
            'has_locale' => $t?->locale === ($locale ?? app()->getLocale()),
        ];
    }

    public function toSearchableArray(): array
    {
        $this->loadMissing('translations');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'status' => $this->verification_status->value,
            'title_fa' => $this->translation('fa')?->title,
            'title_en' => $this->translation('en')?->title,
            'summary_fa' => $this->translation('fa')?->summary,
            'summary_en' => $this->translation('en')?->summary,
            'body' => strip_tags($this->translations->pluck('body')->implode(' ')),
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->verification_status === ContentStatus::Approved;
    }
}
