<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Models\Conversation;
use App\Models\User;

class MarkConversationRead
{
    public function handle(Conversation $conversation, User $user): void
    {
        $last = $conversation->messages()->max('id');
        if ($last) {
            $conversation->members()->where('user_id', $user->id)->update(['last_read_message_id' => $last, 'last_read_at' => now()]);
        }
    }
}
