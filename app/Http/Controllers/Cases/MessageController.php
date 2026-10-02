<?php

namespace App\Http\Controllers\Cases;

use App\Domain\Cases\Models\SupportCase;
use App\Domain\Messaging\Actions\MarkConversationRead;
use App\Domain\Messaging\Actions\SendMessage;
use App\Http\Controllers\Controller;
use App\Services\Files\SecureFileStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    public function store(Request $request, SupportCase $case, SendMessage $send): RedirectResponse
    {
        Gate::authorize('participate', $case);
        $conversation = $case->conversation;
        abort_unless($conversation && $conversation->hasMember($request->user()), 403);

        $data = $request->validate([
            'body' => ['nullable', 'required_without_all:attachments,voice', 'string', 'max:5000'],
            'reply_to_id' => ['nullable', 'integer'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => SecureFileStorage::documentRules(),
            'voice' => ['nullable', ...SecureFileStorage::voiceRules()],
            'voice_duration' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $send->handle($conversation, $request->user(), $data['body'] ?? null, $request->file('attachments', []), $request->file('voice'), $data['reply_to_id'] ?? null, $data['voice_duration'] ?? null);

        return back();
    }

    /** Lightweight polling fallback when websockets are unavailable. */
    public function since(Request $request, SupportCase $case, MarkConversationRead $read): JsonResponse
    {
        Gate::authorize('view', $case);
        $conversation = $case->conversation;
        abort_unless($conversation && $conversation->hasMember($request->user()), 403);
        $after = (int) $request->query('after', 0);
        $messages = $conversation->messages()->where('id', '>', $after)->with(['user:id,name', 'attachments', 'replyTo.user:id,name'])->orderBy('id')->limit(100)->get();
        $read->handle($conversation, $request->user());

        return response()->json([
            'messages' => $messages->map->toBubble(),
            'members' => $conversation->members()->get(['user_id', 'last_read_message_id']),
        ]);
    }
}
