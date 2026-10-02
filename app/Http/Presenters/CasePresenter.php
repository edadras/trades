<?php

namespace App\Http\Presenters;

use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Matching\Enums\MatchStatus;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

/**
 * Builds the case payloads for the UI. What is included depends on who is looking:
 * internal notes only for staff, confidential business data only after an expert accepts, etc.
 */
class CasePresenter
{
    public function card(SupportCase $case): array
    {
        return [
            'id' => $case->id,
            'number' => $case->number,
            'title' => $case->title ?: Str::limit((string) $case->summary, 90),
            'status' => $case->status->value,
            'step' => $case->status->step(),
            'urgency' => $case->urgency?->value,
            'category' => $case->category?->toOption(),
            'subcategory' => $case->subcategory?->toOption(),
            'classification_source' => $case->classification_source?->value,
            'confidence' => $case->confidence,
            'is_sensitive' => $case->is_sensitive,
            'next_action' => $case->next_action,
            'next_action_owner' => $case->next_action_owner,
            'next_action_due_at' => $case->next_action_due_at?->toIso8601String(),
            'business' => $case->relationLoaded('business') ? ['id' => $case->business->id, 'name' => $case->business->trade_name] : null,
            'submitted_at' => $case->submitted_at?->toIso8601String(),
            'updated_at' => $case->updated_at?->toIso8601String(),
            'created_at' => $case->created_at?->toIso8601String(),
        ];
    }

    public function show(SupportCase $case, User $viewer, string $context): array
    {
        $case->loadMissing([
            'business.owner', 'category', 'subcategory', 'latestAnalysis.category', 'latestAnalysis.subcategory',
            'answers', 'documents.uploader', 'tasks.assignee', 'appointments.organizer', 'outcome.recorder',
            'matches.expertProfile.user', 'matches.expertProfile.languages', 'matches.expertProfile.categories',
            'activeExperts.user', 'recommendedContents.translations', 'recommendedContents.category', 'caseManager', 'conversation',
        ]);

        $canInternal = Gate::forUser($viewer)->allows('viewInternalNotes', $case);
        $isStaff = $viewer->isStaff();
        $analysis = $case->latestAnalysis;

        $events = $case->events()->with('user:id,name')->when(! $canInternal, fn ($q) => $q->where('visibility', 'team'))->limit(100)->get();
        $notes = $case->notes()->with('user:id,name')->when(! $canInternal, fn ($q) => $q->where('visibility', 'team'))->get();
        $gate = Gate::forUser($viewer);
        $isBusiness = $case->business->hasMember($viewer);
        $confidential = $gate->allows('viewConfidential', $case);
        $relation = match (true) {
            $isBusiness => 'owner',
            $isStaff => 'staff',
            $confidential => 'case_team',
            default => 'verified_expert',
        };
        $outcome = $case->outcome;

        $conversation = $case->conversation;
        $messages = $conversation && $conversation->hasMember($viewer)
            ? $conversation->messages()->with(['user:id,name', 'attachments', 'replyTo.user:id,name'])->latest('id')->limit(80)->get()->reverse()->values()->map->toBubble()
            : collect();

        return $this->card($case) + [
            'context' => $context,
            'description' => $case->description,
            'actions_taken' => $case->actions_taken,
            'data_consent' => [
                'ai_processing' => $case->consents('ai_processing'),
                'share_with_foreign_experts' => $case->consents('share_with_foreign_experts'),
                'anonymized_learning' => $case->consents('anonymized_learning'),
            ],
            'is_priority' => $case->is_priority,
            'referral' => ['source' => $case->referral_source, 'partner' => $case->partner?->translate('name')],
            'confidential_access' => $confidential,
            'voice' => [
                'has_voice' => (bool) $case->voice_path,
                'transcript' => $case->voice_transcript,
            ],
            'input_mode' => $case->input_mode,
            'summary' => $case->summary,
            'locale' => $case->locale,
            'business_profile' => array_merge($case->business->anonymousProfile(), [
                'name' => $case->business->trade_name,
                'description' => $case->business->description,
            ], $case->business->visibleSensitiveFields($relation)),
            'answers' => $case->answers->map(fn ($a) => ['key' => $a->question_key, 'question' => $a->question, 'answer' => $a->answer])->all(),
            'analysis' => $analysis ? [
                'version' => $analysis->version,
                'category' => $analysis->category?->toOption(),
                'subcategory' => $analysis->subcategory?->toOption(),
                'urgency' => $analysis->urgency?->value,
                'confidence' => $analysis->confidence,
                'summary' => $analysis->summary,
                'facts' => $analysis->facts ?? [],
                'missing_information' => $analysis->missing_information ?? [],
                'suggested_actions' => $analysis->suggested_actions ?? [],
                'guidance' => $analysis->guidance ?? [],
                'safety_flags' => $analysis->safety_flags ?? [],
                'is_sensitive' => $analysis->is_sensitive,
                'needs_expert' => $analysis->needs_expert,
                'verification_state' => $analysis->verification_state->value,
                'provider' => $isStaff ? $analysis->provider.' / '.$analysis->model : null,
                'created_at' => $analysis->created_at->toIso8601String(),
            ] : null,
            'documents' => $confidential ? $case->documents->map(fn ($d) => $d->fileSummary() + ['uploader' => $d->uploader?->name])->all() : [],
            'collaboration_requests' => $case->collaborationRequests()->with(['servicePath', 'requester', 'reviewer'])->get()->map->toCard()->all(),
            'recommended_contents' => $case->recommendedContents->map(fn ($a) => $a->toCard() + ['relevance' => (float) $a->pivot->relevance])->all(),
            'matches' => $case->matches->filter(fn ($m) => $isStaff || $m->status !== MatchStatus::Withdrawn)->map(fn ($m) => [
                'id' => $m->id,
                'score' => $m->score,
                'status' => $m->status->value,
                'source' => $m->source,
                'reasons' => $m->localizedReasons(),
                'breakdown' => $m->breakdown,
                'expert' => $m->expertProfile->toCard($isStaff ? 'staff' : 'public'),
                'engagement_model' => $m->engagement_model,
            ])->values()->all(),
            'experts' => $case->caseExperts()->with('expertProfile.user', 'expertProfile.languages', 'expertProfile.categories')->where('status', 'active')->get()
                ->map(fn ($ce) => $ce->expertProfile->toCard($isStaff ? 'staff' : 'case_team') + [
                    'role' => $ce->role, 'joined_at' => $ce->joined_at?->toIso8601String(),
                    'engagement_model' => $ce->engagement_model, 'engagement_terms' => $ce->engagement_terms,
                    'is_me' => $ce->expertProfile->user_id === $viewer->id,
                ])->all(),
            'past_experts' => $case->caseExperts()->with('expertProfile.user')->whereIn('status', ['left', 'removed'])->get()
                ->map(fn ($ce) => ['name' => $ce->expertProfile->user?->name, 'status' => $ce->status, 'reason' => $ce->leave_reason, 'left_at' => $ce->left_at?->toIso8601String()])->all(),
            'case_manager' => $case->caseManager?->only(['id', 'name']),
            'tasks' => $case->tasks->sortBy(fn ($t) => sprintf('%d-%015d', in_array($t->status->value, ['done', 'cancelled'], true) ? 1 : 0, $t->due_at?->timestamp ?? 999999999999))->map->toCard()->values()->all(),
            'appointments' => $case->appointments->map->toCard()->all(),
            'notes' => $notes->map(fn ($n) => ['id' => $n->id, 'body' => $n->body, 'visibility' => $n->visibility, 'user' => $n->user?->name, 'created_at' => $n->created_at->toIso8601String()])->all(),
            'timeline' => $events->map(fn ($e) => ['id' => $e->id, 'type' => $e->type, 'data' => $e->data, 'user' => $e->user?->name, 'visibility' => $e->visibility, 'created_at' => $e->created_at->toIso8601String()])->all(),
            'conversation' => $conversation && $conversation->hasMember($viewer) ? [
                'id' => $conversation->id,
                'messages' => $messages->all(),
                'members' => $conversation->users()->get()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'role' => $u->pivot->role, 'last_read_message_id' => $u->pivot->last_read_message_id])->all(),
            ] : null,
            'outcome' => $outcome ? [
                'outcome' => $outcome->outcome->value, 'reason' => $outcome->reason, 'result_summary' => $outcome->result_summary,
                'recorded_by' => $outcome->recorder?->name, 'created_at' => $outcome->created_at->toIso8601String(),
                'confirmation_status' => $outcome->confirmation_status, 'confirmed_by' => $outcome->confirmer?->name,
                'confirmed_at' => $outcome->confirmed_at?->toIso8601String(), 'dispute_reason' => $outcome->dispute_reason,
                'auto_confirm_on' => $outcome->confirmation_status === 'pending' ? $outcome->created_at->copy()->addDays(config('platform.outcome_confirmation_days'))->toIso8601String() : null,
            ] : null,
            'outcome_history' => $case->outcomes()->whereNotNull('superseded_at')->with('recorder:id,name')->get()->map(fn ($o) => [
                'outcome' => $o->outcome->value, 'reason' => $o->reason, 'status' => $o->confirmation_status, 'recorded_by' => $o->recorder?->name, 'created_at' => $o->created_at->toIso8601String(),
            ])->all(),
            'survey' => $case->surveys()->where('user_id', $viewer->id)->first()?->only(['rating', 'comment', 'dissatisfaction_reason', 'problem_solved', 'would_recommend_expert']),
            'reviews' => $isStaff ? $case->reviews()->with(['reviewer:id,name', 'aiCategory', 'finalCategory'])->get()->map(fn ($r) => [
                'id' => $r->id, 'status' => $r->status, 'reason' => $r->reason, 'decision' => $r->decision, 'reviewer' => $r->reviewer?->name,
                'ai_category' => $r->aiCategory?->translate('name'), 'final_category' => $r->finalCategory?->translate('name'),
                'ai_urgency' => $r->ai_urgency, 'final_urgency' => $r->final_urgency, 'ai_confidence' => $r->ai_confidence,
                'category_agreed' => $r->category_agreed, 'urgency_agreed' => $r->urgency_agreed, 'notes' => $r->notes,
                'reviewed_at' => $r->reviewed_at?->toIso8601String(), 'created_at' => $r->created_at->toIso8601String(),
            ])->all() : [],
            'stepper' => $this->stepper($case),
            'can' => [
                'participate' => Gate::forUser($viewer)->allows('participate', $case),
                'decide_matches' => $gate->allows('decideMatches', $case) && in_array($case->status, [CaseStatus::ExpertProposed, CaseStatus::Accepted, CaseStatus::InProgress, CaseStatus::Waiting], true),
                'confirm_outcome' => $gate->allows('confirmOutcome', $case) && $outcome?->confirmation_status === 'pending',
                'reopen' => $gate->allows('reopen', $case),
                'request_collaboration' => $gate->allows('requestCollaboration', $case),
                'leave' => $gate->allows('leave', $case),
                'release_experts' => ! $case->isClosed() && ($isBusiness || $gate->allows('assign', $case)),
                'edit_details' => $isBusiness && ! $case->isClosed(),
                'record_outcome' => Gate::forUser($viewer)->allows('recordOutcome', $case),
                'close' => $gate->allows('close', $case) && $outcome?->confirmation_status === 'confirmed' && ($case->status->canTransitionTo(CaseStatus::Closed) || in_array($case->status, [CaseStatus::InProgress, CaseStatus::Waiting], true)),
                'rate' => Gate::forUser($viewer)->allows('rate', $case),
                'review' => Gate::forUser($viewer)->allows('review', $case),
                'assign' => Gate::forUser($viewer)->allows('assign', $case),
                'internal_notes' => $canInternal,
                'edit_problem' => Gate::forUser($viewer)->allows('update', $case),
                'continue_intake' => $case->status === CaseStatus::Draft && $case->business->hasMember($viewer),
            ],
        ];
    }

    /** The 7-step progress indicator: Submit, Complete info, AI analysis, Expert review, Choose supporter, Action, Result. */
    public function stepper(SupportCase $case): array
    {
        $current = $case->status->step();
        $skippedReview = $case->status->step() > 4 && ! $case->reviews()->exists();
        $keys = ['submit', 'complete_info', 'ai_analysis', 'expert_review', 'choose_supporter', 'action', 'result'];

        return collect($keys)->map(fn ($key, $i) => [
            'key' => $key,
            'state' => match (true) {
                $case->status === CaseStatus::Closed => 'done',
                $i + 1 === 4 && $skippedReview => 'skipped',
                $i + 1 < $current => 'done',
                $i + 1 === $current => 'current',
                default => 'upcoming',
            },
        ])->all();
    }
}
