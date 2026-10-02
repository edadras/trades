<?php

namespace App\Domain\Cases;

use App\Domain\Cases\Models\CaseEvent;
use App\Domain\Cases\Models\SupportCase;

class CaseTimeline
{
    /** @param array<string, mixed> $data */
    public function record(SupportCase $case, string $type, array $data = [], ?int $userId = null, string $visibility = 'team'): CaseEvent
    {
        return CaseEvent::create([
            'case_id' => $case->id,
            'user_id' => $userId ?? auth()->id(),
            'type' => $type,
            'visibility' => $visibility,
            'data' => $data ?: null,
        ]);
    }
}
