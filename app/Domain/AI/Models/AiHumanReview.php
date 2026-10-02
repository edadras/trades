<?php

namespace App\Domain\AI\Models;

use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiHumanReview extends Model
{
    public const REASONS = ['low_confidence', 'sensitive', 'safety', 'manual', 'escalated', 'missing_information'];

    public const DECISIONS = ['confirmed', 'edited', 'info_requested', 'escalated', 'closed'];

    protected $fillable = [
        'case_id', 'ai_analysis_id', 'reviewer_id', 'reason', 'status', 'ai_category_id', 'ai_subcategory_id',
        'ai_urgency', 'ai_confidence', 'final_category_id', 'final_subcategory_id', 'final_urgency',
        'category_agreed', 'urgency_agreed', 'decision', 'notes', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'ai_confidence' => 'float',
            'category_agreed' => 'boolean',
            'urgency_agreed' => 'boolean',
            'reviewed_at' => 'datetime',
            'notes' => 'encrypted',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(AiAnalysis::class, 'ai_analysis_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function aiCategory(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'ai_category_id');
    }

    public function finalCategory(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'final_category_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }
}
