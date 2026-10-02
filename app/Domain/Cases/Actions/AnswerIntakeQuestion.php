<?php

namespace App\Domain\Cases\Actions;

use App\Domain\AI\Intake\IntakeInterviewer;
use App\Domain\AI\Models\AiSession;
use App\Domain\AI\AIManager;
use App\Domain\Cases\Models\CaseAnswer;
use App\Domain\Cases\Models\SupportCase;
use App\Models\User;

/**
 * Drives the AI intake conversation: stores the user's answer to the pending question
 * and asks the next one (or signals that the case file is complete).
 */
class AnswerIntakeQuestion
{
    public function __construct(private readonly IntakeInterviewer $interviewer, private readonly AIManager $ai) {}

    public function session(SupportCase $case, User $user): AiSession
    {
        return AiSession::firstOrCreate(
            ['case_id' => $case->id, 'purpose' => 'intake'],
            ['user_id' => $user->id, 'provider' => $this->ai->provider()->name(), 'model' => $this->ai->provider()->model()],
        );
    }

    /** @return array{key: string, question: string}|null the next question */
    public function next(SupportCase $case, User $user): ?array
    {
        $session = $this->session($case, $user);
        if ($session->status === 'completed') {
            return null;
        }

        $question = $this->interviewer->nextQuestion($case->fresh());
        if (! $question) {
            $session->update(['status' => 'completed', 'completed_at' => now()]);

            return null;
        }

        $exists = CaseAnswer::where('case_id', $case->id)->where('question_key', $question['key'])->exists();
        if (! $exists) {
            CaseAnswer::create(['case_id' => $case->id, 'question_key' => $question['key'], 'question' => $question['question'], 'source' => $session->provider === 'local' ? 'engine' : 'ai']);
            $session->messages()->create(['role' => 'assistant', 'content' => $question['question'], 'meta' => ['key' => $question['key']]]);
            $session->increment('turns');
        }

        return $question;
    }

    /** @return array{key: string, question: string}|null */
    public function answer(SupportCase $case, User $user, string $key, ?string $answer): ?array
    {
        $session = $this->session($case, $user);
        $record = CaseAnswer::where('case_id', $case->id)->where('question_key', $key)->firstOrFail();
        $record->update(['answer' => filled($answer) ? trim($answer) : '—']);
        $session->messages()->create(['role' => 'user', 'content' => $record->answer, 'meta' => ['key' => $key]]);

        return $this->next($case, $user);
    }

    public function skipRemaining(SupportCase $case, User $user): void
    {
        CaseAnswer::where('case_id', $case->id)->whereNull('answer')->update(['answer' => null]);
        CaseAnswer::where('case_id', $case->id)->whereNull('answer')->delete();
        $this->session($case, $user)->update(['status' => 'completed', 'completed_at' => now()]);
    }
}
