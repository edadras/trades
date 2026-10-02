<?php

namespace App\Domain\Cases\Actions;

use App\Domain\AI\Models\AiClassification;
use App\Domain\AI\Models\AiHumanReview;
use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\CaseTimeline;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Enums\Urgency;
use App\Domain\Cases\Enums\VerificationState;
use App\Domain\Cases\Models\CaseNote;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Matching\Actions\RunMatching;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Applies a case expert's decision on an AI analysis: confirm, edit category/urgency, request more
 * information or escalate. Agreement between AI and human is stored for accuracy measurement.
 */
class ApplyHumanReview
{
    public function __construct(
        private readonly TransitionCaseStatus $transition,
        private readonly CaseTimeline $timeline,
        private readonly RunMatching $matching,
        private readonly CaseNotifier $notifier,
        private readonly RequestCaseDocument $requestDocument,
    ) {}

    /**
     * @param  array{decision: string, category_id?: int|null, subcategory_id?: int|null, urgency?: string|null, notes?: string|null, request?: string|null, run_matching?: bool}  $data
     */
    public function handle(SupportCase $case, User $reviewer, array $data): AiHumanReview
    {
        $case->loadMissing('latestAnalysis');
        $analysis = $case->latestAnalysis;

        $review = $case->reviews()->where('status', 'pending')->first() ?? new AiHumanReview([
            'case_id' => $case->id,
            'ai_analysis_id' => $analysis?->id,
            'reason' => 'manual',
            'ai_category_id' => $analysis?->category_id,
            'ai_subcategory_id' => $analysis?->subcategory_id,
            'ai_urgency' => $analysis?->urgency?->value,
            'ai_confidence' => $analysis?->confidence,
        ]);

        $decision = $data['decision'];
        $finalCategory = $data['category_id'] ?? $case->category_id;
        $finalSub = array_key_exists('subcategory_id', $data) ? $data['subcategory_id'] : $case->subcategory_id;
        $finalUrgency = $data['urgency'] ?? $case->urgency?->value;

        DB::transaction(function () use ($case, $reviewer, $data, $review, $decision, $finalCategory, $finalSub, $finalUrgency, $analysis) {
            $review->fill([
                'reviewer_id' => $reviewer->id,
                'status' => 'completed',
                'final_category_id' => $finalCategory,
                'final_subcategory_id' => $finalSub,
                'final_urgency' => $finalUrgency,
                'category_agreed' => $review->ai_category_id ? (int) $review->ai_category_id === (int) $finalCategory : null,
                'urgency_agreed' => $review->ai_urgency ? $review->ai_urgency === $finalUrgency : null,
                'decision' => $decision,
                'notes' => $data['notes'] ?? null,
                'reviewed_at' => now(),
            ])->save();

            $source = $decision === 'confirmed' ? VerificationState::HumanApproved : VerificationState::ExpertVerified;
            $case->forceFill([
                'category_id' => $finalCategory,
                'subcategory_id' => $finalSub,
                'urgency' => $finalUrgency ? Urgency::from($finalUrgency) : $case->urgency,
                'classification_source' => $source,
                'case_manager_id' => $case->case_manager_id ?? $reviewer->id,
                'first_reviewed_at' => $case->first_reviewed_at ?? now(),
            ])->save();
            $analysis?->update(['verification_state' => $source]);

            AiClassification::create([
                'case_id' => $case->id, 'ai_analysis_id' => $analysis?->id, 'category_id' => $finalCategory,
                'subcategory_id' => $finalSub, 'urgency' => $finalUrgency, 'confidence' => 1, 'source' => $source,
                'created_by' => $reviewer->id,
            ]);

            if (filled($data['notes'] ?? null)) {
                CaseNote::create(['case_id' => $case->id, 'user_id' => $reviewer->id, 'visibility' => 'internal', 'body' => $data['notes']]);
            }

            $this->timeline->record($case, 'human_review_completed', [
                'decision' => $decision, 'category_agreed' => $review->category_agreed, 'urgency_agreed' => $review->urgency_agreed,
            ], $reviewer->id);
        });

        $case->refresh();
        match ($decision) {
            'info_requested' => $this->requestInfo($case, $reviewer, (string) ($data['request'] ?? '')),
            'escalated' => $this->timeline->record($case, 'case_escalated', ['notes' => $data['notes'] ?? null], $reviewer->id, 'internal'),
            default => $this->advance($case, (bool) ($data['run_matching'] ?? true)),
        };

        return $review;
    }

    private function advance(SupportCase $case, bool $runMatching): void
    {
        if (in_array($case->status, [CaseStatus::HumanReview, CaseStatus::AiProcessing, CaseStatus::Waiting], true)) {
            if ($case->status === CaseStatus::Waiting) {
                $this->transition->handle($case, CaseStatus::HumanReview);
            }
            $this->transition->handle($case, CaseStatus::Ready, 'review_confirmed');
        }
        if ($runMatching && $case->needs_expert && $case->status === CaseStatus::Ready) {
            $this->matching->handle($case->fresh());
        }
    }

    private function requestInfo(SupportCase $case, User $reviewer, string $request): void
    {
        if ($case->status === CaseStatus::HumanReview) {
            $this->transition->handle($case, CaseStatus::Waiting, 'info_requested');
        }
        $this->requestDocument->handle($case, $reviewer, $request ?: __('cases.default_info_request', [], $case->locale));
    }
}
