<?php

namespace App\Domain\AI\Models;

use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['ai_session_id', 'role', 'content', 'meta'];

    protected function casts(): array
    {
        return ['content' => 'encrypted', 'meta' => 'array'];
    }
}
