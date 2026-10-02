<?php

namespace App\Domain\Compliance\Models;

use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    public const CATEGORIES = ['service_quality', 'expert_conduct', 'outcome_dispute', 'privacy', 'technical', 'other'];

    public const STATUSES = ['open', 'in_review', 'resolved', 'rejected'];

    protected $fillable = ['user_id', 'case_id', 'category', 'subject', 'body', 'status', 'assignee_id', 'resolution', 'resolved_at'];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'resolved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'in_review']);
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'subject' => $this->subject,
            'body' => $this->body,
            'status' => $this->status,
            'resolution' => $this->resolution,
            'user' => $this->user?->only(['id', 'name']),
            'assignee' => $this->assignee?->only(['id', 'name']),
            'case_number' => $this->case?->number,
            'created_at' => $this->created_at->toIso8601String(),
            'resolved_at' => $this->resolved_at?->toIso8601String(),
        ];
    }
}
