<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Models\CaseNote;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;

class AddCaseNote
{
    public function __construct(private readonly CaseTimeline $timeline) {}

    public function handle(SupportCase $case, User $user, string $body, string $visibility): CaseNote
    {
        $visibility = $user->isStaff() ? $visibility : 'team';
        $note = CaseNote::create(['case_id' => $case->id, 'user_id' => $user->id, 'visibility' => $visibility, 'body' => $body]);
        $this->timeline->record($case, 'note_added', [], $user->id, $visibility === 'internal' ? 'internal' : 'team');

        return $note;
    }
}
