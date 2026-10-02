<?php

namespace App\Notifications;

use App\Domain\Business\Actions\ManageTeam;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitationNotification extends Notification
{
    public function __construct(private readonly string $business, private readonly string $inviter, private readonly string $url, private readonly string $lang) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('team.mail.subject', ['business' => $this->business], $this->lang))
            ->line(__('team.mail.line', ['inviter' => $this->inviter, 'business' => $this->business], $this->lang))
            ->action(__('team.mail.action', [], $this->lang), $this->url)
            ->line(__('team.mail.expires', ['days' => ManageTeam::TTL_DAYS], $this->lang));
    }
}
