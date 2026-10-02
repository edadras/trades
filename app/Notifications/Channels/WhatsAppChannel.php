<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp') || ! $notifiable->phone) {
            return;
        }

        match (config('platform.messaging_channels.whatsapp')) {
            default => Log::channel('stack')->info('[whatsapp]', ['to' => substr((string) $notifiable->phone, 0, -4).'****', 'text' => $notification->toWhatsApp($notifiable)]),
        };
    }
}
