<?php

namespace App\Domain\Experts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertVerification extends Model
{
    protected $fillable = ['expert_profile_id', 'reviewer_id', 'status', 'checklist', 'notes'];

    protected function casts(): array
    {
        return ['checklist' => 'array'];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
