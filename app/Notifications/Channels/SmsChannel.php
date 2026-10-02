<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Pluggable SMS channel. Drivers: "log" (default) records the message; add a gateway driver
 * by extending resolveDriver() without touching notification code.
 */
class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms') || ! $notifiable->phone) {
            return;
        }
        $this->deliver((string) $notifiable->phone, $notification->toSms($notifiable));
    }

    protected function deliver(string $to, string $text): void
    {
        match (config('platform.messaging_channels.sms')) {
            default => Log::channel('stack')->info('[sms]', ['to' => substr($to, 0, -4).'****', 'text' => $text]),
        };
    }
}
