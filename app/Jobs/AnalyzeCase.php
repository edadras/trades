<?php

namespace App\Jobs;

use App\Domain\Cases\Actions\AnalyzeCase as AnalyzeCaseAction;
use App\Domain\Cases\Models\SupportCase;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyzeCase implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60, 180];

    public function __construct(public int $caseId)
    {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return (string) $this->caseId;
    }

    public function handle(AnalyzeCaseAction $action): void
    {
        $case = SupportCase::find($this->caseId);
        if ($case) {
            $action->handle($case);
        }
    }
}
