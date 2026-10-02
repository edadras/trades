<?php

namespace App\Events;

use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CaseStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(public SupportCase $case, public CaseStatus $from, public CaseStatus $to) {}
}
