<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** WhatsApp delivery. Drivers: "log" (default) or "cloud_api" (Meta WhatsApp Cloud API). */
class WhatsAppChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWhatsApp') || ! $notifiable->phone) {
            return;
        }
        $this->deliver((string) $notifiable->phone, $notification->toWhatsApp($notifiable));
    }

    public function deliver(string $to, string $text): void
    {
        $config = config('platform.messaging_channels');

        match ($config['whatsapp']) {
            'cloud_api' => Http::withToken((string) $config['whatsapp_cloud']['token'])->timeout(15)
                ->post("https://graph.facebook.com/{$config['whatsapp_cloud']['version']}/{$config['whatsapp_cloud']['phone_number_id']}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => preg_replace('/\D+/', '', $to),
                    'type' => 'text',
                    'text' => ['body' => $text],
                ])->throw(),
            default => Log::info('[whatsapp]', ['to' => substr($to, 0, -4).'****', 'text' => $text]),
        };
    }
}
