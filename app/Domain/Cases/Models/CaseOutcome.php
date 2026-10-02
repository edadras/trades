<?php

namespace App\Domain\Cases\Models;

use App\Domain\Cases\Enums\OutcomeType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseOutcome extends Model
{
    protected $fillable = ['case_id', 'recorded_by', 'outcome', 'reason', 'result_summary', 'metrics'];

    protected function casts(): array
    {
        return ['outcome' => OutcomeType::class, 'metrics' => 'array'];
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
