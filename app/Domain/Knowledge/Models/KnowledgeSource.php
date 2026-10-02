<?php

namespace App\Domain\Knowledge\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeSource extends Model
{
    protected $fillable = ['name', 'publisher', 'url', 'type', 'reliability'];
}
