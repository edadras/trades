<?php

namespace App\Actions\Fortify;

use App\Domain\Business\Actions\ManageTeam;
use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\BusinessInvitation;
use App\Domain\Business\Models\Partner;
use App\Domain\Identity\Actions\RecordConsent;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private readonly RecordConsent $consent) {}

    /** @param array<string, string> $input */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:190', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'account_type' => ['required', Rule::in(['business', 'supporter'])],
            'company_name' => ['nullable', 'string', 'max:150', Rule::requiredIf(fn () => ($input['account_type'] ?? null) === 'business' && blank($input['invitation'] ?? null))],
            'ref' => ['nullable', 'string', 'max:40'],
            'invitation' => ['nullable', 'string', 'max:80'],
            'terms' => ['accepted'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => strtolower($input['email']),
                'password' => $input['password'],
                'locale' => app()->getLocale(),
            ]);

            $invitation = filled($input['invitation'] ?? null) ? BusinessInvitation::findByToken($input['invitation']) : null;
            if ($invitation && $invitation->isPending() && $invitation->email === $user->email) {
                // Joining an existing business team instead of creating a new business.
                app(ManageTeam::class)->accept($invitation, $user);
                $user->markEmailAsVerified();
            } elseif ($input['account_type'] === 'business') {
                $user->assignRole(Role::Business->value);
                $partner = filled($input['ref'] ?? null) ? Partner::where('referral_code', strtoupper($input['ref']))->where('is_active', true)->first() : null;
                $business = Business::create([
                    'owner_id' => $user->id,
                    'partner_id' => $partner?->id,
                    'trade_name' => $input['company_name'],
                    'preferred_language' => app()->getLocale(),
                ]);
                $business->members()->attach($user->id, ['role' => 'owner']);
            }
            // Supporters only receive the "supporter" role once verified; until then they complete an application.

            $this->consent->handle($user, 'terms', true);
            $this->consent->handle($user, 'privacy', true);
            if (! empty($input['marketing'])) {
                $this->consent->handle($user, 'marketing', true);
            }

            return $user;
        });
    }
}
