<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Domain\Messaging\Models\MessageAttachment;
use App\Events\MessageSent;
use App\Models\User;
use App\Notifications\PlatformNotification;
use App\Services\Files\SecureFileStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SendMessage
{
    public function __construct(private readonly SecureFileStorage $files) {}

    /** @param array<int, UploadedFile> $attachments */
    public function handle(Conversation $conversation, User $sender, ?string $body, array $attachments = [], ?UploadedFile $voice = null, ?int $replyToId = null, ?int $voiceDuration = null): Message
    {
        $memberIds = $conversation->members()->pluck('user_id');
        $mentions = $this->mentions((string) $body, $conversation);

        $message = DB::transaction(function () use ($conversation, $sender, $body, $attachments, $voice, $replyToId, $mentions, $voiceDuration) {
            $message = $conversation->messages()->create([
                'user_id' => $sender->id,
                'reply_to_id' => $replyToId && $conversation->messages()->whereKey($replyToId)->exists() ? $replyToId : null,
                'type' => $voice ? 'voice' : (blank($body) && $attachments ? 'file' : 'text'),
                'body' => $body,
                'mentions' => $mentions ?: null,
            ]);

            $uploads = $voice ? [[$voice, $voiceDuration]] : [];
            foreach ($attachments as $file) {
                $uploads[] = [$file, null];
            }
            foreach ($uploads as [$file, $duration]) {
                $attachment = MessageAttachment::create($this->files->store($file, "conversations/{$conversation->id}") + ['message_id' => $message->id, 'duration_seconds' => $duration]);
                $this->files->scan($attachment);
            }

            $conversation->update(['last_message_at' => now()]);
            $conversation->members()->where('user_id', $sender->id)->update(['last_read_message_id' => $message->id, 'last_read_at' => now()]);

            return $message;
        });

        $message->load(['user', 'attachments', 'replyTo.user']);
        broadcast(new MessageSent($message))->toOthers();

        $recipients = User::whereIn('id', $memberIds)->where('id', '!=', $sender->id)->get();
        foreach ($recipients as $recipient) {
            $case = $conversation->case;
            $recipient->notify(new PlatformNotification('new_message', [
                'case' => $case?->number,
                'from' => $sender->name,
                'mentioned' => in_array($recipient->id, $mentions, true) ? 1 : 0,
            ], $case ? app(CaseNotifier::class)->caseUrlFor($recipient, $case).'?tab=workspace' : null));
        }

        return $message;
    }

    /** @return array<int, int> user ids mentioned as @Name */
    private function mentions(string $body, Conversation $conversation): array
    {
        if (! str_contains($body, '@')) {
            return [];
        }

        return $conversation->users()->get()
            ->filter(fn ($u) => str_contains($body, '@'.$u->name) || str_contains($body, '@'.str_replace(' ', '_', $u->name)))
            ->pluck('id')->values()->all();
    }
}
