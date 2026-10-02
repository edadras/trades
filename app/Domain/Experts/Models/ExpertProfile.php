<?php

namespace App\Domain\Experts\Models;

use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Experts\Enums\ExpertVerificationStatus;
use App\Domain\Matching\Models\ExpertMatch;
use App\Models\User;
use App\Support\HasPrivacySettings;
use Database\Factories\ExpertProfileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpertProfile extends Model
{
    /** @use HasFactory<ExpertProfileFactory> */
    use HasFactory, HasPrivacySettings, SoftDeletes;

    public const COLLABORATION_TYPES = ['consultation', 'project', 'mentoring', 'training', 'problem_review', 'introduction', 'pro_bono'];

    /** How the supporter is compensated: voluntary, free, subsidised by the programme, or commercial. */
    public const SUPPORT_MODELS = ['voluntary', 'free', 'subsidized', 'commercial'];

    public const SUPPORTER_TYPES = ['individual', 'organization'];

    protected $fillable = [
        'user_id', 'supporter_type', 'organization_name', 'support_models', 'headline', 'bio', 'country', 'city', 'timezone', 'years_experience', 'industries',
        'serves_countries', 'collaboration_types', 'certifications', 'linkedin_url', 'max_active_cases',
        'is_available', 'nda_accepted_at', 'nda_version', 'verification_status', 'verified_at', 'avg_response_minutes',
    ];

    protected function casts(): array
    {
        return [
            'industries' => 'array',
            'serves_countries' => 'array',
            'collaboration_types' => 'array',
            'support_models' => 'array',
            'certifications' => 'array',
            'is_available' => 'boolean',
            'nda_accepted_at' => 'datetime',
            'verified_at' => 'datetime',
            'verification_status' => ExpertVerificationStatus::class,
        ];
    }

    protected static function newFactory(): ExpertProfileFactory
    {
        return ExpertProfileFactory::new();
    }

    public static function defaultPrivacy(): array
    {
        return [
            'city' => 'public',
            'linkedin_url' => 'case_team',
            'certifications' => 'public',
            'email' => 'case_team',
            'phone' => 'private',
        ];
    }

    /** Contact fields come from the user account but obey the expert's privacy choices. */
    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user?->phone;
    }

    public function isForeignTo(?string $country): bool
    {
        return $country !== null && $this->country !== $country;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(ExpertSkill::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CaseCategory::class, 'expert_skills')->withPivot(['level', 'years'])->withTimestamps();
    }

    public function languages(): HasMany
    {
        return $this->hasMany(ExpertLanguage::class);
    }

    public function availability(): HasMany
    {
        return $this->hasMany(ExpertAvailability::class)->orderBy('weekday')->orderBy('starts_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ExpertDocument::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(ExpertVerification::class)->latest();
    }

    public function matches(): HasMany
    {
        return $this->hasMany(ExpertMatch::class);
    }

    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(SupportCase::class, 'case_experts', 'expert_profile_id', 'case_id')->withPivot(['role', 'status', 'joined_at'])->withTimestamps();
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('verification_status', ExpertVerificationStatus::Verified->value);
    }

    public function isVerified(): bool
    {
        return $this->verification_status === ExpertVerificationStatus::Verified;
    }

    public function activeCaseCount(): int
    {
        return $this->cases()->wherePivot('status', 'active')
            ->whereNotIn('cases.status', [CaseStatus::Resolved->value, CaseStatus::Closed->value])->count();
    }

    /** Public card used in matching proposals and the public directory. */
    /**
     * Public card used in matching proposals and the directory. $relation decides which
     * privacy-controlled fields are included (public | verified_expert | case_team | owner | staff).
     */
    public function toCard(string $relation = 'public'): array
    {
        $visible = $this->visibleSensitiveFields($relation);

        return [
            'id' => $this->id,
            'name' => $this->supporter_type === 'organization' && $this->organization_name ? $this->organization_name : $this->user?->name,
            'contact_person' => $this->supporter_type === 'organization' ? $this->user?->name : null,
            'supporter_type' => $this->supporter_type,
            'support_models' => $this->support_models ?? [],
            'city' => $visible['city'] ?? null,
            'linkedin_url' => $visible['linkedin_url'] ?? null,
            'certifications' => $visible['certifications'] ?? [],
            'email' => $visible['email'] ?? null,
            'phone' => $visible['phone'] ?? null,
            'avatar' => $this->user?->avatar_path,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'country' => $this->country,
            'years_experience' => $this->years_experience,
            'industries' => $this->industries ?? [],
            'languages' => $this->languages->pluck('language')->all(),
            'skills' => $this->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->translate('name'), 'level' => $c->pivot->level])->all(),
            'is_available' => $this->is_available,
            'verified' => $this->isVerified(),
            'collaboration_types' => $this->collaboration_types ?? [],
        ];
    }
}
