<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS delivery. Drivers: "log" (default, records the message), "kavenegar" (Iranian gateway),
 * "webhook" (POST to any gateway/relay that accepts {to, text}).
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

    public function deliver(string $to, string $text): void
    {
        $config = config('platform.messaging_channels');

        match ($config['sms']) {
            'kavenegar' => Http::asForm()->timeout(15)->post(
                "https://api.kavenegar.com/v1/{$config['kavenegar']['api_key']}/sms/send.json",
                array_filter(['receptor' => $to, 'message' => $text, 'sender' => $config['kavenegar']['sender']]),
            )->throw(),
            'webhook' => Http::withToken((string) $config['sms_webhook']['token'])->timeout(15)
                ->post((string) $config['sms_webhook']['url'], ['to' => $to, 'text' => $text])->throw(),
            default => Log::info('[sms]', ['to' => substr($to, 0, -4).'****', 'text' => $text]),
        };
    }
}
