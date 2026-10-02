<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /** @param array<string, string> $input */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'timezone' => ['nullable', 'timezone'],
            'locale' => ['nullable', Rule::in(config('platform.locales'))],
        ])->validateWithBag('updateProfileInformation');

        $emailChanged = $input['email'] !== $user->email;
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'phone' => $input['phone'] ?? $user->phone,
            'timezone' => $input['timezone'] ?? $user->timezone,
            'locale' => $input['locale'] ?? $user->locale,
        ] + ($emailChanged ? ['email_verified_at' => null] : []))->save();

        if ($emailChanged && $user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
        }
    }
}
