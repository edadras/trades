<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Cases\Models\SupportCase;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['case_id', 'type', 'subject', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime'];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(SupportCase::class, 'case_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ConversationMember::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_members')->withPivot(['role', 'last_read_message_id', 'last_read_at'])->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->where('user_id', $user->id)->exists();
    }

    public function unreadCountFor(User $user): int
    {
        $lastRead = (int) $this->members()->where('user_id', $user->id)->value('last_read_message_id');

        return $this->messages()->where('id', '>', $lastRead)->where('user_id', '!=', $user->id)->count();
    }
}
