<?php

namespace App\Domain\Experts\Actions;

use App\Domain\Experts\Enums\ExpertVerificationStatus;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Identity\Actions\RecordConsent;
use App\Domain\Identity\Enums\Permission;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Validation\ValidationException;

class SubmitExpertApplication
{
    public const NDA_VERSION = '2026-10';

    public function __construct(private readonly RecordConsent $consent) {}

    public function handle(ExpertProfile $profile, User $user): ExpertProfile
    {
        if ($profile->skills()->count() === 0 || $profile->languages()->count() === 0) {
            throw ValidationException::withMessages(['skills' => __('experts.errors.incomplete')]);
        }

        $profile->update([
            'nda_accepted_at' => now(),
            'nda_version' => self::NDA_VERSION,
            'verification_status' => ExpertVerificationStatus::Submitted,
        ]);
        $profile->verifications()->create(['status' => ExpertVerificationStatus::Submitted->value]);
        $this->consent->handle($user, 'nda', true, self::NDA_VERSION);

        foreach (User::permission(Permission::ExpertsVerify->value)->get() as $staff) {
            $staff->notify(new PlatformNotification('review_required', ['case' => $user->name], route('admin.experts.show', ['locale' => $staff->locale ?: 'fa', 'expert' => $profile->id])));
        }

        return $profile;
    }
}
