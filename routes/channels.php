<?php

use App\Domain\Messaging\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', fn ($user, $id) => (int) $user->id === (int) $id);

Broadcast::channel('conversations.{conversation}', function ($user, Conversation $conversation) {
    return $conversation->hasMember($user) && (! $conversation->case || $user->can('view', $conversation->case));
});
