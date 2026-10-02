<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * One notification type for every platform event. Copy comes from lang/{locale}/notifications.php
 * so each recipient receives it in their own language.
 *
 * Events: case_updated, expert_suggested, expert_invited, expert_accepted, expert_declined, new_message,
 * document_requested, deadline_approaching, appointment_scheduled, appointment_reminder, case_resolved,
 * review_required, task_assigned, expert_verified, plus outcome confirmation, team, compliance and pilot events.
 */
class PlatformNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const EVENTS = [
        'case_updated', 'expert_suggested', 'expert_invited', 'expert_accepted', 'expert_declined', 'new_message',
        'document_requested', 'deadline_approaching', 'appointment_scheduled', 'appointment_reminder', 'case_resolved',
        'review_required', 'task_assigned', 'expert_verified', 'outcome_confirmation_requested', 'outcome_confirmed',
        'outcome_auto_confirmed', 'outcome_disputed', 'case_reopened', 'expert_left', 'legal_review_required',
        'collaboration_request_decided', 'complaint_received', 'complaint_updated', 'data_request_received',
        'data_request_ready', 'data_request_decided', 'pilot_report_ready',
    ];

    /** Events that are worth an email by default (the rest are in-app only unless the user opts in). */
    private const MAIL_BY_DEFAULT = ['expert_suggested', 'expert_invited', 'expert_accepted', 'document_requested', 'deadline_approaching', 'appointment_reminder', 'case_resolved', 'review_required', 'expert_verified', 'outcome_confirmation_requested', 'outcome_disputed', 'legal_review_required', 'complaint_updated', 'data_request_ready', 'data_request_decided', 'pilot_report_ready'];

    /** @param array<string, mixed> $params */
    public function __construct(public string $event, public array $params = [], public ?string $url = null) {}

    public function via(object $notifiable): array
    {
        $prefs = $notifiable->notification_preferences ?? [];
        $channels = ['database'];
        if ((bool) ($prefs[$this->event]['mail'] ?? in_array($this->event, self::MAIL_BY_DEFAULT, true))) {
            $channels[] = 'mail';
        }
        if (! empty($prefs[$this->event]['sms']) && $notifiable->phone) {
            $channels[] = SmsChannel::class;
        }
        if (! empty($prefs[$this->event]['whatsapp']) && $notifiable->phone) {
            $channels[] = WhatsAppChannel::class;
        }

        return $channels;
    }

    private function line(object $notifiable, string $part): string
    {
        $locale = $notifiable->locale ?? config('app.locale');

        return __("notifications.{$this->event}.{$part}", collect($this->params)->map(fn ($v) => is_scalar($v) ? (string) $v : '')->all(), $locale);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->line($notifiable, 'title'))->line($this->line($notifiable, 'body'));

        return $this->url ? $mail->action(__('notifications.open', [], $notifiable->locale ?? 'fa'), $this->url) : $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => $this->event,
            'params' => $this->params,
            'url' => $this->url,
        ];
    }

    public function toSms(object $notifiable): string
    {
        return $this->line($notifiable, 'title').' — '.$this->line($notifiable, 'body');
    }

    public function toWhatsApp(object $notifiable): string
    {
        return $this->toSms($notifiable).($this->url ? "\n".$this->url : '');
    }
}
