<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginCodeNotification extends Notification
{
    public function __construct(private readonly string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->locale ?? config('app.locale');

        return (new MailMessage)
            ->subject(__('auth.otp_subject', [], $locale))
            ->line(__('auth.otp_line', ['code' => $this->code], $locale))
            ->line(__('auth.otp_expires', ['minutes' => config('platform.otp_ttl_minutes')], $locale));
    }
}
