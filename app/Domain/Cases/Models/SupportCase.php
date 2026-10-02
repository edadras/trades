<?php

namespace App\Domain\Cases\Models;

use App\Domain\AI\Models\AiAnalysis;
use App\Domain\AI\Models\AiHumanReview;
use App\Domain\AI\Models\AiSession;
use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\Partner;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Enums\Urgency;
use App\Domain\Cases\Enums\VerificationState;
use App\Domain\Compliance\Models\CollaborationRequest;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Domain\Matching\Models\ExpertMatch;
use App\Domain\Messaging\Models\Conversation;
use App\Models\User;
use Database\Factories\SupportCaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * A business problem tracked end-to-end ("پرونده"). Named SupportCase because `case` is reserved in PHP.
 */
class SupportCase extends Model
{
    /** @use HasFactory<SupportCaseFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'cases';

    protected $fillable = [
        'number', 'business_id', 'partner_id', 'referral_source', 'actions_taken', 'data_consent', 'is_priority', 'created_by', 'case_manager_id', 'title', 'description', 'input_mode', 'voice_path',
        'voice_transcript', 'locale', 'category_id', 'subcategory_id', 'urgency', 'confidence', 'classification_source',
        'summary', 'is_sensitive', 'needs_expert', 'status', 'next_action', 'next_action_owner', 'next_action_due_at',
        'submitted_at', 'first_reviewed_at', 'ready_at', 'accepted_at', 'resolved_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CaseStatus::class,
            'urgency' => Urgency::class,
            'classification_source' => VerificationState::class,
            'description' => 'encrypted',
            'actions_taken' => 'encrypted',
            'data_consent' => 'array',
            'is_priority' => 'boolean',
            'voice_transcript' => 'encrypted',
            'summary' => 'encrypted',
            'confidence' => 'float',
            'is_sensitive' => 'boolean',
            'needs_expert' => 'boolean',
            'next_action_due_at' => 'datetime',
            'submitted_at' => 'datetime',
            'first_reviewed_at' => 'datetime',
            'ready_at' => 'datetime',
            'accepted_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): SupportCaseFactory
    {
        return SupportCaseFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $case) {
            $case->number ??= static::nextNumber();
            $case->status ??= CaseStatus::Draft;
        });
    }

    /** CASE-2026-000001, sequential per year. */
    public static function nextNumber(): string
    {
        $year = now()->year;
        $prefix = "CASE-{$year}-";

        return DB::transaction(function () use ($prefix) {
            $last = static::withTrashed()->where('number', 'like', $prefix.'%')->lockForUpdate()->orderByDesc('number')->value('number');
            $seq = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

            return $prefix.str_pad((string) $seq, 6, '0', STR_PAD_LEFT);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function caseManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'case_manager_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'category_id');
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(CaseCategory::class, 'subcategory_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(CaseStatusHistory::class, 'case_id')->latest('id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CaseDocument::class, 'case_id')->latest();
    }

    public function answers(): HasMany
    {
        return $this->hasMany(CaseAnswer::class, 'case_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CaseNote::class, 'case_id')->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CaseTask::class, 'case_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(CaseEvent::class, 'case_id')->latest('id');
    }

    /** The current (not superseded) outcome. */
    public function outcome(): HasOne
    {
        return $this->hasOne(CaseOutcome::class, 'case_id')->whereNull('superseded_at')->latestOfMany();
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(CaseOutcome::class, 'case_id')->latest('id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function collaborationRequests(): HasMany
    {
        return $this->hasMany(CollaborationRequest::class, 'case_id')->latest();
    }

    /** Per-case data-use choices made by the business when submitting. */
    public function consents(string $key): bool
    {
        $defaults = ['ai_processing' => true, 'share_with_foreign_experts' => true, 'anonymized_learning' => false];

        return (bool) (($this->data_consent ?? [])[$key] ?? $defaults[$key] ?? false);
    }

    /** Whether a confirmed outcome exists (required before closing). */
    public function hasConfirmedOutcome(): bool
    {
        return $this->outcome()->where('confirmation_status', 'confirmed')->exists();
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(SatisfactionSurvey::class, 'case_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'case_id')->orderBy('starts_at');
    }

    public function aiSessions(): HasMany
    {
        return $this->hasMany(AiSession::class, 'case_id');
    }

    public function analyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class, 'case_id')->orderByDesc('version');
    }

    public function latestAnalysis(): HasOne
    {
        return $this->hasOne(AiAnalysis::class, 'case_id')->latestOfMany('version');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AiHumanReview::class, 'case_id')->latest();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(ExpertMatch::class, 'case_id')->orderByDesc('score');
    }

    public function caseExperts(): HasMany
    {
        return $this->hasMany(CaseExpert::class, 'case_id');
    }

    public function experts(): BelongsToMany
    {
        return $this->belongsToMany(ExpertProfile::class, 'case_experts', 'case_id')->withPivot(['role', 'status', 'joined_at'])->withTimestamps();
    }

    public function activeExperts(): BelongsToMany
    {
        return $this->experts()->wherePivot('status', 'active');
    }

    public function recommendedContents(): BelongsToMany
    {
        return $this->belongsToMany(KnowledgeArticle::class, 'case_recommended_contents', 'case_id')
            ->withPivot(['relevance', 'source', 'viewed_at'])->withTimestamps()->orderByPivot('relevance', 'desc');
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class, 'case_id');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn($this->qualifyColumn('status'), [CaseStatus::Draft->value, CaseStatus::Resolved->value, CaseStatus::Closed->value]);
    }

    public function scopeReal(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), '!=', CaseStatus::Draft->value);
    }

    public function isClosed(): bool
    {
        return $this->status === CaseStatus::Closed;
    }

    /** Whether the user is one of the active experts on the case. */
    public function hasActiveExpert(User $user): bool
    {
        return $this->activeExperts()->where('expert_profiles.user_id', $user->id)->exists();
    }

    /** The narrative the AI and reviewers work from: typed text plus voice transcript. */
    public function problemText(): string
    {
        $title = $this->title && ! str_starts_with(trim((string) $this->description), preg_replace('/[.…\s]+$/u', '', $this->title)) ? $this->title : null;

        return trim(implode("\n\n", array_filter([$title, $this->description, $this->voice_transcript])));
    }
}
