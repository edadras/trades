<?php

namespace App\Domain\AI\Intake;

use App\Domain\AI\AIManager;
use App\Domain\AI\Classification\CaseClassifier;
use App\Domain\AI\Data\AnalysisResult;
use App\Domain\AI\Knowledge\GuidanceComposer;
use App\Domain\AI\Knowledge\KnowledgeRetriever;
use App\Domain\AI\Safety\SafetyGuard;
use App\Domain\AI\Summarization\CaseSummarizer;
use App\Domain\Cases\Models\SupportCase;

/**
 * Orchestrates the full intake analysis of a case. Pure: it does not persist anything.
 */
class IntakeAnalyzer
{
    public function __construct(
        private readonly AIManager $ai,
        private readonly CaseClassifier $classifier,
        private readonly CaseSummarizer $summarizer,
        private readonly SafetyGuard $safety,
        private readonly KnowledgeRetriever $retriever,
        private readonly GuidanceComposer $composer,
        private readonly IntakeInterviewer $interviewer,
    ) {}

    public function analyze(SupportCase $case): AnalysisResult
    {
        $case->loadMissing(['business', 'answers', 'documents']);
        $answers = $case->answers->map(fn ($a) => ['question' => $a->question, 'answer' => $a->answer])->all();
        $fullText = trim($case->problemText()."\n".collect($answers)->pluck('answer')->filter()->implode("\n"));
        $locale = $case->locale ?: 'fa';

        $classification = $this->classifier->classify($fullText, $case->business?->industry);
        $flags = $this->safety->inspect($fullText);
        $isSensitive = $flags !== [] || (bool) $classification->category?->is_sensitive || (bool) $classification->subcategory?->is_sensitive;

        $summary = $this->summarizer->summarize($case->problemText());
        $facts = $this->summarizer->facts($fullText, $answers);
        $retrieved = $this->retriever->retrieve($fullText, $classification, $case->business?->industry, $case->business?->country);
        $guidance = $this->composer->compose($fullText, $summary, $classification, $retrieved, $flags, $locale);

        $missing = [];
        if ($case->documents->isEmpty()) {
            foreach ($guidance['required_documents'] as $doc) {
                $missing[] = $doc['text'];
            }
        }
        $unanswered = $this->interviewer->heuristicQuestion($case);
        if ($unanswered) {
            $missing[] = $unanswered['question'];
        }

        return new AnalysisResult(
            classification: $classification,
            summary: $summary,
            facts: $facts,
            missingInformation: array_values(array_unique($missing)),
            suggestedActions: array_column($guidance['suggested_actions'], 'text'),
            recommendedContents: $retrieved->map(fn ($r) => ['id' => $r['article']->id, 'slug' => $r['article']->slug, 'score' => $r['score']])->all(),
            guidance: $guidance,
            safetyFlags: $flags,
            isSensitive: $isSensitive,
            needsExpert: (bool) $guidance['needs_expert'],
            provider: $this->ai->provider()->name(),
            model: $this->ai->provider()->model(),
        );
    }
}
