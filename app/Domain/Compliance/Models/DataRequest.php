<?php

namespace App\Domain\Compliance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A user's request to export or delete their personal data. */
class DataRequest extends Model
{
    public const TYPES = ['export', 'delete'];

    protected $fillable = ['user_id', 'type', 'status', 'reason', 'file_path', 'handled_by', 'resolution', 'completed_at'];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }
}
