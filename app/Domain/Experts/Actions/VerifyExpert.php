<?php

namespace App\Domain\Experts\Actions;

use App\Domain\Cases\Actions\ChangeCaseTeam;
use App\Domain\Experts\Enums\ExpertVerificationStatus;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Identity\AuditLogger;
use App\Domain\Identity\Enums\Role;
use App\Domain\Matching\Enums\MatchStatus;
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
        if (in_array($status, [ExpertVerificationStatus::Suspended, ExpertVerificationStatus::Rejected], true)) {
            $this->revoke($profile, $reviewer, $notes);
        }
        $this->audit->log('expert.verification.'.$status->value, $profile, ['notes' => $notes]);
        $profile->user->notify(new PlatformNotification('expert_verified', ['status' => $status->value], route('expert.dashboard', ['locale' => $profile->user->locale ?: 'fa'])));

        return $profile;
    }

    /** A suspended or rejected supporter loses the supporter panel, open proposals and active cases. */
    private function revoke(ExpertProfile $profile, User $reviewer, ?string $notes): void
    {
        $profile->user->removeRole(Role::Supporter->value);
        $profile->matches()->whereIn('status', [MatchStatus::Proposed->value, MatchStatus::Invited->value])
            ->update(['status' => MatchStatus::Withdrawn->value, 'decision_reason' => 'expert_'.$profile->verification_status->value]);
        $reason = $notes ?: __('experts.revoked_reason');
        foreach ($profile->cases()->wherePivot('status', 'active')->get() as $case) {
            app(ChangeCaseTeam::class)->release($case, $profile, $reviewer, 'removed', $reason);
        }
    }
}
