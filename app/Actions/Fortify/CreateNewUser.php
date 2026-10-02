<?php

namespace App\Actions\Fortify;

use App\Domain\Business\Models\Business;
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
            'company_name' => ['required_if:account_type,business', 'nullable', 'string', 'max:150'],
            'terms' => ['accepted'],
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => strtolower($input['email']),
                'password' => $input['password'],
                'locale' => app()->getLocale(),
            ]);

            if ($input['account_type'] === 'business') {
                $user->assignRole(Role::Business->value);
                $business = Business::create([
                    'owner_id' => $user->id,
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
