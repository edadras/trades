<?php

namespace App\Domain\Cases\Models;

use App\Domain\Cases\Enums\ParticipantRole;
use App\Domain\Cases\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseTask extends Model
{
    protected $fillable = [
        'case_id', 'created_by', 'assignee_id', 'title', 'description', 'owner_role', 'status', 'is_next_action',
        'due_at', 'reminded_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'owner_role' => ParticipantRole::class,
            'is_next_action' => 'boolean',
            'due_at' => 'datetime',
            'reminded_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereIn('status', [TaskStatus::Open->value, TaskStatus::InProgress->value]);
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'owner_role' => $this->owner_role?->value,
            'status' => $this->status->value,
            'is_next_action' => $this->is_next_action,
            'due_at' => $this->due_at?->toIso8601String(),
            'overdue' => $this->due_at?->isPast() && in_array($this->status, [TaskStatus::Open, TaskStatus::InProgress], true),
            'assignee' => $this->assignee?->only(['id', 'name']),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
