<?php

namespace App\Domain\Pilot\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Weekly report of results and errors (cases, KPIs, AI corrections, incidents, dissatisfaction). */
class PilotReport extends Model
{
    protected $fillable = ['pilot_program_id', 'week_start', 'week_end', 'metrics', 'errors', 'notes', 'author_id'];

    protected function casts(): array
    {
        return ['week_start' => 'date', 'week_end' => 'date', 'metrics' => 'array', 'errors' => 'array'];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
