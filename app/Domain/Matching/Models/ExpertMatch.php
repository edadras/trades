<?php

namespace App\Domain\Matching\Models;

use App\Domain\Cases\Models\SupportCase;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Matching\Enums\MatchStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertMatch extends Model
{
    protected $fillable = [
        'case_id', 'expert_profile_id', 'score', 'breakdown', 'reasons', 'status', 'source', 'proposed_by',
        'decision_reason', 'business_decided_at', 'expert_decided_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'breakdown' => 'array',
            'reasons' => 'array',
            'status' => MatchStatus::class,
            'business_decided_at' => 'datetime',
            'expert_decided_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function expertProfile(): BelongsTo
    {
        return $this->belongsTo(ExpertProfile::class);
    }

    /** Reasons in the requested locale. */
    public function localizedReasons(?string $locale = null): array
    {
        $locale ??= app()->getLocale();

        return array_map(fn ($r) => $r[$locale] ?? $r['en'] ?? '', $this->reasons ?? []);
    }
}
