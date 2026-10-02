<?php

namespace App\Domain\AI\Models;

use App\Domain\Cases\Enums\Urgency;
use App\Domain\Cases\Enums\VerificationState;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Cases\Models\SupportCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    protected $fillable = [
        'case_id', 'version', 'category_id', 'subcategory_id', 'urgency', 'confidence', 'summary', 'facts',
        'missing_information', 'suggested_actions', 'required_expertises', 'guidance', 'safety_flags', 'is_sensitive',
        'needs_expert', 'verification_state', 'provider', 'model', 'latency_ms', 'raw',
    ];

    protected function casts(): array
    {
        return [
            'urgency' => Urgency::class,
            'verification_state' => VerificationState::class,
            'confidence' => 'float',
            'summary' => 'encrypted',
            'facts' => 'array',
            'missing_information' => 'array',
            'suggested_actions' => 'array',
            'required_expertises' => 'array',
            'guidance' => 'array',
            'safety_flags' => 'array',
            'is_sensitive' => 'boolean',
            'needs_expert' => 'boolean',
            'raw' => 'array',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'subcategory_id');
    }
}
