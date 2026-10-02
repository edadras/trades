<?php

namespace App\Domain\Cases\Actions;

use App\Domain\AI\Intake\IntakeAnalyzer;
use App\Domain\AI\Models\AiAnalysis;
use App\Domain\AI\Models\AiClassification;
use App\Domain\AI\Models\AiHumanReview;
use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Enums\VerificationState;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Matching\Actions\RunMatching;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Runs the AI intake engine and routes the case:
 *  - low confidence, sensitive or safety-flagged → Human Review queue
 *  - otherwise → Ready, then automatic expert matching when an expert is needed.
 * The AI never makes a final decision: its output is stored as "ai_suggested".
 */
class AnalyzeCase
{
    public function __construct(
        private readonly IntakeAnalyzer $analyzer,
        private readonly TransitionCaseStatus $transition,
        private readonly CaseTimeline $timeline,
        private readonly RunMatching $matching,
        private readonly CaseNotifier $notifier,
    ) {}

    public function handle(SupportCase $case): AiAnalysis
    {
        if ($case->status === CaseStatus::Submitted) {
            $this->transition->handle($case, CaseStatus::AiProcessing, null, null);
        }

        $started = microtime(true);
        $result = $this->analyzer->analyze($case);
        $latency = (int) ((microtime(true) - $started) * 1000);
        $c = $result->classification;
        $threshold = (float) config('ai.confidence_threshold');
        $needsReview = $c->confidence < $threshold || $result->isSensitive || ! $c->category || $c->category->slug === 'general';

        $analysis = DB::transaction(function () use ($case, $result, $c, $latency, $needsReview) {
            $analysis = AiAnalysis::create([
                'case_id' => $case->id,
                'version' => (int) $case->analyses()->max('version') + 1,
                'category_id' => $c->category?->id,
                'subcategory_id' => $c->subcategory?->id,
                'urgency' => $c->urgency,
                'confidence' => $c->confidence,
                'summary' => $result->summary,
                'facts' => $result->facts,
                'missing_information' => $result->missingInformation,
                'suggested_actions' => $result->suggestedActions,
                'required_expertises' => $c->requiredExpertises,
                'guidance' => $result->guidance,
                'safety_flags' => $result->safetyFlags,
                'is_sensitive' => $result->isSensitive,
                'needs_expert' => $result->needsExpert,
                'verification_state' => VerificationState::AiSuggested,
                'provider' => $result->provider,
                'model' => $result->model,
                'latency_ms' => $latency,
                'raw' => $result->toArray(),
            ]);

            AiClassification::create([
                'case_id' => $case->id, 'ai_analysis_id' => $analysis->id, 'category_id' => $c->category?->id,
                'subcategory_id' => $c->subcategory?->id, 'urgency' => $c->urgency->value, 'confidence' => $c->confidence,
                'source' => VerificationState::AiSuggested,
            ]);

            // Human-verified classifications are never overwritten by a later AI run.
            $keepHuman = $case->classification_source && $case->classification_source !== VerificationState::AiSuggested;
            $case->forceFill(array_filter([
                'category_id' => $keepHuman ? null : $c->category?->id,
                'subcategory_id' => $keepHuman ? null : $c->subcategory?->id,
                'urgency' => $keepHuman ? null : $c->urgency,
                'confidence' => $c->confidence,
                'classification_source' => $keepHuman ? null : VerificationState::AiSuggested,
            ], fn ($v) => ! is_null($v)) + [
                'summary' => $result->summary,
                'is_sensitive' => $case->is_sensitive || $result->isSensitive,
                'needs_expert' => $result->needsExpert,
            ])->save();

            $case->recommendedContents()->syncWithoutDetaching(collect($result->recommendedContents)->mapWithKeys(fn ($r) => [$r['id'] => ['relevance' => $r['score'], 'source' => 'ai']])->all());

            $first = $result->guidance['suggested_actions'][0]['text'] ?? null;
            if ($first && blank($case->next_action)) {
                $case->update(['next_action' => $first, 'next_action_owner' => 'business']);
            }

            $this->timeline->record($case, 'ai_analysis_completed', [
                'version' => $analysis->version, 'confidence' => $c->confidence, 'category' => $c->category?->slug,
                'needs_review' => $needsReview,
            ], null);

            if ($needsReview && ! $case->reviews()->where('status', 'pending')->exists()) {
                AiHumanReview::create([
                    'case_id' => $case->id,
                    'ai_analysis_id' => $analysis->id,
                    'reason' => $result->safetyFlags ? 'safety' : ($result->isSensitive ? 'sensitive' : 'low_confidence'),
                    'ai_category_id' => $c->category?->id,
                    'ai_subcategory_id' => $c->subcategory?->id,
                    'ai_urgency' => $c->urgency->value,
                    'ai_confidence' => $c->confidence,
                ]);
            }

            return $analysis;
        });

        $case->refresh();
        if ($needsReview) {
            if ($case->status !== CaseStatus::HumanReview) {
                $this->transition->handle($case, CaseStatus::HumanReview, 'ai_needs_review', null);
            }
            $this->notifyReviewers($case);
        } elseif (in_array($case->status, [CaseStatus::AiProcessing, CaseStatus::HumanReview], true)) {
            $this->transition->handle($case, CaseStatus::Ready, 'ai_confident', null);
            if ($case->needs_expert) {
                $this->matching->handle($case->fresh());
            }
        }

        return $analysis;
    }

    private function notifyReviewers(SupportCase $case): void
    {
        $reviewers = User::permission(Permission::CasesReview->value)->get();
        foreach ($reviewers as $reviewer) {
            $this->notifier->notifyUser($reviewer, $case, 'review_required', [], route('review.cases.show', ['locale' => $reviewer->locale ?: 'fa', 'case' => $case->number]));
        }
    }
}
