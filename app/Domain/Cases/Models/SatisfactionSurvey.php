<?php

namespace App\Domain\Cases\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatisfactionSurvey extends Model
{
    public const DISSATISFACTION_REASONS = ['slow_response', 'expert_mismatch', 'guidance_not_useful', 'problem_not_solved', 'communication', 'cost_or_terms', 'other'];

    protected $fillable = ['case_id', 'user_id', 'rating', 'comment', 'dissatisfaction_reason', 'problem_solved', 'would_recommend_expert'];

    protected function casts(): array
    {
        return ['problem_solved' => 'boolean', 'would_recommend_expert' => 'boolean'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }
}
