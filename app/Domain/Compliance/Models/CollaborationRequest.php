<?php

namespace App\Domain\Compliance\Models;

use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Request to activate a review-required service path on a case; decided by legal & compliance. */
class CollaborationRequest extends Model
{
    protected $fillable = ['case_id', 'service_path_id', 'requested_by', 'description', 'status', 'reviewer_id', 'conditions', 'reviewed_at'];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function servicePath(): BelongsTo
    {
        return $this->belongsTo(ServicePath::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'path' => $this->servicePath?->toOption(),
            'description' => $this->description,
            'status' => $this->status,
            'conditions' => $this->conditions,
            'requester' => $this->requester?->name,
            'reviewer' => $this->reviewer?->name,
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'case_number' => $this->case?->number,
        ];
    }
}
