<?php

namespace App\Domain\Knowledge\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeTranslation extends Model
{
    protected $fillable = ['knowledge_article_id', 'locale', 'title', 'summary', 'body', 'checklist', 'seo_title', 'seo_description'];

    protected function casts(): array
    {
        return ['checklist' => 'array'];
    }
}
