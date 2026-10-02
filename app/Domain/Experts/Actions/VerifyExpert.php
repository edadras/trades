<?php

namespace App\Domain\Experts\Actions;

use App\Domain\Experts\Enums\ExpertVerificationStatus;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Identity\AuditLogger;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use App\Notifications\PlatformNotification;

class VerifyExpert
{
    public function __construct(private readonly AuditLogger $audit) {}

    /** @param array<string, bool> $checklist */
    public function handle(ExpertProfile $profile, User $reviewer, ExpertVerificationStatus $status, array $checklist = [], ?string $notes = null): ExpertProfile
    {
        $profile->update([
            'verification_status' => $status,
            'verified_at' => $status === ExpertVerificationStatus::Verified ? now() : null,
        ]);
        $profile->verifications()->create([
            'reviewer_id' => $reviewer->id,
            'status' => $status->value,
            'checklist' => $checklist,
            'notes' => $notes,
        ]);
        if ($status === ExpertVerificationStatus::Verified && ! $profile->user->hasRole(Role::Supporter->value)) {
            $profile->user->assignRole(Role::Supporter->value);
        }
        $this->audit->log('expert.verification.'.$status->value, $profile, ['notes' => $notes]);
        $profile->user->notify(new PlatformNotification('expert_verified', ['status' => $status->value], route('expert.dashboard', ['locale' => $profile->user->locale ?: 'fa'])));

        return $profile;
    }
}
