<?php

namespace App\Domain\Cases\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'case_status_history';

    protected $fillable = ['case_id', 'user_id', 'from_status', 'to_status', 'reason'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
