<?php

namespace App\Domain\Cases\Models;

use App\Domain\Cases\Enums\OutcomeType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseOutcome extends Model
{
    protected $fillable = ['case_id', 'recorded_by', 'outcome', 'reason', 'result_summary', 'metrics', 'confirmation_status', 'confirmed_by', 'confirmed_at', 'dispute_reason', 'superseded_at'];

    protected function casts(): array
    {
        return ['outcome' => OutcomeType::class, 'metrics' => 'array', 'confirmed_at' => 'datetime', 'superseded_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
