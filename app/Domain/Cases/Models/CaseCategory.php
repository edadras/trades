<?php

namespace App\Domain\Cases\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseCategory extends Model
{
    use HasTranslations;

    protected array $translatable = ['name', 'description'];

    protected $fillable = ['parent_id', 'slug', 'name', 'description', 'keywords', 'icon', 'is_sensitive', 'default_urgency', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['keywords' => 'array', 'is_sensitive' => 'boolean', 'is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** @return array<string, mixed> */
    public function toOption(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translate('name'),
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
            'is_sensitive' => $this->is_sensitive,
        ];
    }
}
