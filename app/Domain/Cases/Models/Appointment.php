<?php

namespace App\Domain\Cases\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    protected $fillable = [
        'case_id', 'organizer_id', 'title', 'agenda', 'starts_at', 'ends_at', 'location', 'meeting_url',
        'attendee_ids', 'status', 'reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reminded_at' => 'datetime',
            'attendee_ids' => 'array',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'agenda' => $this->agenda,
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'location' => $this->location,
            'meeting_url' => $this->meeting_url,
            'status' => $this->status,
            'organizer' => $this->organizer?->only(['id', 'name']),
            'case_number' => $this->case?->number,
        ];
    }
}
