<?php

namespace App\Domain\Cases\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Immutable timeline entry. visibility: team (all participants) | internal (staff only). */
class CaseEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['case_id', 'user_id', 'type', 'visibility', 'data'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
