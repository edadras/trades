<?php

namespace App\Domain\Cases\Models;

use App\Domain\Experts\Models\ExpertProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseExpert extends Model
{
    protected $fillable = ['case_id', 'expert_profile_id', 'expert_match_id', 'role', 'status', 'joined_at', 'left_at'];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime', 'left_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function expertProfile(): BelongsTo
    {
        return $this->belongsTo(ExpertProfile::class);
    }
}
