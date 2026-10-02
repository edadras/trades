<?php

namespace App\Domain\Business\Models;

use App\Domain\Cases\Models\SupportCase;
use App\Domain\Pilot\Models\PilotProgram;
use App\Models\User;
use App\Support\HasPrivacySettings;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory, HasPrivacySettings, SoftDeletes;

    public const ONBOARDING_STEPS = 9;

    protected $fillable = [
        'owner_id', 'partner_id', 'pilot_program_id', 'eligibility_status', 'eligibility_reason', 'trade_name', 'legal_name', 'registration_number', 'logo_path', 'country', 'province', 'city',
        'industry', 'employees_range', 'size', 'founded_year', 'website', 'description', 'products_services',
        'preferred_language', 'contact_name', 'contact_email', 'contact_phone', 'address', 'main_needs',
        'onboarding_step', 'onboarding_completed_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'registration_number' => 'encrypted',
            'contact_name' => 'encrypted',
            'contact_email' => 'encrypted',
            'contact_phone' => 'encrypted',
            'address' => 'encrypted',
            'main_needs' => 'array',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    protected static function newFactory(): BusinessFactory
    {
        return BusinessFactory::new();
    }

    public static function defaultPrivacy(): array
    {
        return [
            'legal_name' => 'case_team',
            'registration_number' => 'private',
            'contact_name' => 'case_team',
            'contact_email' => 'case_team',
            'contact_phone' => 'case_team',
            'address' => 'private',
            'website' => 'verified_experts',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_members')->withPivot('role')->withTimestamps();
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function pilotProgram(): BelongsTo
    {
        return $this->belongsTo(PilotProgram::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(BusinessInvitation::class)->latest();
    }

    /** Pilot eligibility: a business outside the active pilot's scope cannot open cases until staff admit it. */
    public function canOpenCases(): bool
    {
        return $this->isOnboarded() && ! in_array($this->eligibility_status, ['ineligible', 'waitlisted'], true);
    }

    /** owner | admin | member */
    public function roleOf(User $user): ?string
    {
        if ($this->owner_id === $user->id) {
            return 'owner';
        }

        return $this->members()->whereKey($user->id)->first()?->pivot->role;
    }

    public function canManageTeam(User $user): bool
    {
        return in_array($this->roleOf($user), ['owner', 'admin'], true);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BusinessDocument::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(SupportCase::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->owner_id === $user->id || $this->members()->whereKey($user->id)->exists();
    }

    public function isOnboarded(): bool
    {
        return ! is_null($this->onboarding_completed_at);
    }

    /** Public, non-sensitive profile shown to anyone involved with a case before acceptance. */
    public function anonymousProfile(): array
    {
        return [
            'industry' => $this->industry,
            'size' => $this->size,
            'country' => $this->country,
            'province' => $this->province,
            'employees_range' => $this->employees_range,
        ];
    }
}
