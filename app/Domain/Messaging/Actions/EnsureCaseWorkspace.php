<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\ConversationMember;

/** Creates the shared case workspace conversation and keeps its membership in sync with the case team (adds and removes). */
class EnsureCaseWorkspace
{
    public function __construct(private readonly CaseNotifier $notifier) {}

    public function handle(SupportCase $case): Conversation
    {
        $conversation = Conversation::firstOrCreate(['case_id' => $case->id, 'type' => 'case'], ['subject' => $case->number]);

        $case->loadMissing('business');
        $participants = $this->notifier->participants($case);
        // People who are no longer on the case (removed team members, replaced managers, released experts) lose access.
        $conversation->members()->whereNotIn('user_id', $participants->pluck('id'))->delete();
        foreach ($participants as $user) {
            $role = match (true) {
                $case->business->hasMember($user) => 'business',
                $user->id === $case->case_manager_id => 'case_manager',
                default => 'expert',
            };
            ConversationMember::firstOrCreate(['conversation_id' => $conversation->id, 'user_id' => $user->id], ['role' => $role]);
        }

        return $conversation;
    }
}
