<?php

namespace App\Domain\Cases\Actions;

use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Models\SatisfactionSurvey;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;

class SubmitSatisfaction
{
    public function __construct(private readonly CaseTimeline $timeline) {}

    /** @param array<string, mixed> $data */
    public function handle(SupportCase $case, User $user, array $data): SatisfactionSurvey
    {
        $survey = SatisfactionSurvey::updateOrCreate(['case_id' => $case->id, 'user_id' => $user->id], [
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'problem_solved' => $data['problem_solved'] ?? null,
            'would_recommend_expert' => $data['would_recommend_expert'] ?? null,
            'dissatisfaction_reason' => $data['rating'] <= 3 ? ($data['dissatisfaction_reason'] ?? null) : null,
        ]);
        $this->timeline->record($case, 'satisfaction_submitted', ['rating' => $survey->rating], $user->id);

        return $survey;
    }
}
