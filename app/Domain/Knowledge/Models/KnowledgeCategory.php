<?php

namespace App\Domain\Knowledge\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeCategory extends Model
{
    use HasTranslations;

    protected array $translatable = ['name', 'description'];

    protected $fillable = ['parent_id', 'slug', 'name', 'description', 'icon', 'color', 'sort_order'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function articles(): HasMany
    {
        return $this->hasMany(KnowledgeArticle::class);
    }

    public function toOption(): array
    {
        return ['id' => $this->id, 'slug' => $this->slug, 'name' => $this->translate('name'), 'icon' => $this->icon, 'color' => $this->color];
    }
}
