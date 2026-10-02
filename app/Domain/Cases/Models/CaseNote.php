<?php

namespace App\Domain\Cases\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/** visibility: internal (platform staff only) | team (everyone on the case). */
class CaseNote extends Model
{
    use SoftDeletes;

    protected $fillable = ['case_id', 'user_id', 'visibility', 'body'];

    protected function casts(): array
    {
        return ['body' => 'encrypted'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
