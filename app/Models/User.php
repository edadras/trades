<?php

namespace App\Models;

use App\Domain\Business\Models\Business;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Identity\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasLocalePreference, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'locale', 'timezone', 'avatar_path', 'status',
        'notification_preferences', 'last_login_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
            'phone' => 'encrypted',
            'notification_preferences' => 'array',
        ];
    }

    public function preferredLocale(): string
    {
        return $this->locale ?: config('app.locale');
    }

    public function ownedBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'owner_id');
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_members')->withPivot('role')->withTimestamps();
    }

    public function expertProfile(): HasOne
    {
        return $this->hasOne(ExpertProfile::class);
    }

    /**
     * The business the user currently acts for: the one chosen with the business switcher (kept in the
     * session) when the user belongs to several businesses, otherwise their first membership.
     */
    public function currentBusiness(): ?Business
    {
        $chosen = app()->bound('session') && session()->isStarted() ? session('current_business_id') : null;
        $query = $this->businesses()->orderBy('business_members.id');

        return ($chosen ? (clone $query)->whereKey($chosen)->first() : null) ?? $query->first();
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(Role::staffValues());
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole([Role::Admin->value, Role::SuperAdmin->value]);
    }

    public function hasTwoFactorEnabled(): bool
    {
        return ! is_null($this->two_factor_secret) && ! is_null($this->two_factor_confirmed_at);
    }

    /** Whether the user wants a given notification event on a given channel. */
    public function wantsNotification(string $event, string $channel): bool
    {
        $prefs = $this->notification_preferences ?? [];

        return (bool) ($prefs[$event][$channel] ?? $channel !== 'sms');
    }

    /** Main area of the app the user lands on after login. */
    public function homeRouteName(): string
    {
        return match (true) {
            $this->isStaff() && $this->can('analytics.view') => 'admin.dashboard',
            $this->isStaff() && $this->can('cases.review') => 'review.index',
            $this->isStaff() && $this->can('knowledge.manage') => 'admin.knowledge.index',
            $this->isStaff() && $this->can('audit.view') => 'admin.audit.index',
            $this->isStaff() && $this->can('experts.view') => 'admin.experts.index',
            $this->isStaff() && $this->can('legal.review') => 'admin.compliance.index',
            $this->hasRole(Role::Supporter->value) => 'expert.dashboard',
            $this->hasRole(Role::Business->value) && $this->currentBusiness() => $this->currentBusiness()->isOnboarded() ? 'dashboard' : 'onboarding.show',
            default => 'expert.profile.edit',
        };
    }
}
