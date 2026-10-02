<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Jobs\AnalyzeCase as AnalyzeCaseJob;
use Illuminate\Validation\ValidationException;

class SubmitCase
{
    public function __construct(private readonly TransitionCaseStatus $transition) {}

    public function handle(SupportCase $case): SupportCase
    {
        if (blank($case->problemText()) && ! $case->voice_path) {
            throw ValidationException::withMessages(['description' => __('cases.errors.empty_problem')]);
        }

        $this->transition->handle($case, CaseStatus::Submitted);
        AnalyzeCaseJob::dispatch($case->id);

        return $case;
    }
}
