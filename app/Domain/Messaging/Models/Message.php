<?php

namespace App\Domain\Messaging\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = ['conversation_id', 'user_id', 'reply_to_id', 'type', 'body', 'mentions', 'edited_at'];

    protected function casts(): array
    {
        return ['body' => 'encrypted', 'mentions' => 'array', 'edited_at' => 'datetime'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reply_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function toBubble(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'body' => $this->body,
            'user' => $this->user ? ['id' => $this->user->id, 'name' => $this->user->name] : null,
            'reply_to' => $this->replyTo ? ['id' => $this->replyTo->id, 'body' => str($this->replyTo->body)->limit(80)->toString(), 'user' => $this->replyTo->user?->name] : null,
            'mentions' => $this->mentions ?? [],
            'attachments' => $this->attachments->map(fn ($a) => $a->fileSummary() + ['duration_seconds' => $a->duration_seconds])->all(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
