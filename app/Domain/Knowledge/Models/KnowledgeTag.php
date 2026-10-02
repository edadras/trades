<?php

namespace App\Domain\Knowledge\Models;

use App\Support\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class KnowledgeTag extends Model
{
    use HasTranslations;

    protected array $translatable = ['name'];

    protected $fillable = ['slug', 'name'];
}
