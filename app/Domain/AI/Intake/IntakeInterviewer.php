<?php

namespace App\Domain\AI\Intake;

use App\Domain\AI\AIManager;
use App\Domain\AI\Classification\CaseClassifier;
use App\Domain\AI\Providers\AIProviderException;
use App\Domain\AI\Safety\PiiRedactor;
use App\Domain\AI\Support\TextNormalizer;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Pilot\Models\AiIncident;
use Illuminate\Support\Facades\Log;

/**
 * Conducts the short follow-up interview that turns a free-form problem into a standard case file.
 * Returns the next question to ask, or null when enough information has been collected.
 */
class IntakeInterviewer
{
    public function __construct(
        private readonly AIManager $ai,
        private readonly CaseClassifier $classifier,
        private readonly PiiRedactor $redactor,
    ) {}

    /** @return array{key: string, question: string}|null */
    public function nextQuestion(SupportCase $case): ?array
    {
        $case->loadMissing(['answers', 'business', 'documents']);
        $asked = $case->answers;
        if ($asked->count() >= config('ai.max_intake_questions')) {
            return null;
        }
        if ($asked->whereNull('answer')->isNotEmpty()) {
            $pending = $asked->whereNull('answer')->first();

            return ['key' => $pending->question_key, 'question' => $pending->question];
        }

        if ($this->ai->usesLanguageModel() && $case->consents('ai_processing')) {
            try {
                return $this->askModel($case);
            } catch (AIProviderException $e) {
                Log::warning('AI intake fell back to heuristics', ['error' => $e->getMessage()]);
                AiIncident::record($this->ai->provider()->name(), 'intake', $e->getMessage(), $case->id);
            }
        }

        return $this->heuristicQuestion($case);
    }

    /** @return array{key: string, question: string}|null */
    public function heuristicQuestion(SupportCase $case): ?array
    {
        $locale = in_array($case->locale, config('platform.locales'), true) ? $case->locale : 'fa';
        $text = $case->problemText().' '.$case->answers->pluck('answer')->implode(' ');
        $normalized = TextNormalizer::normalize($text);
        $askedKeys = $case->answers->pluck('question_key')->all();

        $candidates = [];
        if (! $case->business?->industry) {
            $candidates['industry'] = __('ai.questions.industry', [], $locale);
        }

        $classification = $this->classifier->classify($text, $case->business?->industry, $case->consents('ai_processing'));
        $slug = $classification->category?->slug ?? 'general';
        $taxonomy = collect(config('taxonomy'))->firstWhere('slug', $slug);
        foreach (($taxonomy['questions'][$locale] ?? []) as $i => $question) {
            $candidates["{$slug}_{$i}"] = $question;
        }

        if (! preg_match('/\d|یک|دو|سه|چند|هفته|ماه|سال|week|month|year|since/u', $normalized)) {
            $candidates['timeframe'] = __('ai.questions.timeframe', [], $locale);
        }
        if (blank($case->actions_taken)) {
            $candidates['attempts'] = __('ai.questions.attempts', [], $locale);
        }
        if ($case->documents->isEmpty()) {
            $candidates['documents'] = __('ai.questions.documents', [], $locale);
        }

        foreach ($candidates as $key => $question) {
            if (! in_array($key, $askedKeys, true)) {
                return ['key' => $key, 'question' => $question];
            }
        }

        return null;
    }

    /** @return array{key: string, question: string}|null */
    private function askModel(SupportCase $case): ?array
    {
        $language = $case->locale === 'en' ? 'English' : 'Persian (Farsi)';
        $system = "You are the intake assistant of a business-support platform. Ask ONE short follow-up question at a time, in {$language}, "
            .'to complete a standard case file (industry, timeframe, magnitude/impact, what was tried, available documents). '
            .'Never give advice here and never claim a decision. If information is sufficient, return {"done": true}. '
            .'Otherwise return {"done": false, "key": "snake_case_topic", "question": "..."}.';

        $history = [['role' => 'user', 'content' => $this->redactor->redact(
            'Business industry: '.($case->business?->industry ?? 'unknown')."\nProblem: ".$case->problemText()
        )]];
        foreach ($case->answers as $answer) {
            $history[] = ['role' => 'assistant', 'content' => $answer->question];
            $history[] = ['role' => 'user', 'content' => $this->redactor->redact((string) $answer->answer)];
        }

        $json = $this->ai->provider()->generateJson($system, $history, 0.3);
        if (! empty($json['done']) || empty($json['question'])) {
            return null;
        }

        return ['key' => (string) ($json['key'] ?? 'followup_'.($case->answers->count() + 1)), 'question' => (string) $json['question']];
    }
}
