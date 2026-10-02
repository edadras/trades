<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Consent extends Model
{
    public const UPDATED_AT = null;

    public const TYPES = ['terms', 'privacy', 'data_processing', 'ai_processing', 'marketing', 'nda'];

    protected $fillable = ['user_id', 'type', 'version', 'granted', 'ip_address', 'user_agent'];

    protected function casts(): array
    {
        return ['granted' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
