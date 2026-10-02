<?php

namespace App\Domain\Business\Actions;

use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\BusinessDocument;
use App\Domain\Identity\Actions\RecordConsent;
use App\Domain\Pilot\Actions\EvaluateEligibility;
use App\Models\PrivacySetting;
use App\Models\User;
use App\Services\Files\SecureFileStorage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Multi-step business registration wizard:
 * 1 basics → 2 company → 3 industry → 4 size → 5 region → 6 contact → 7 needs → 8 documents → 9 privacy.
 */
class SaveOnboardingStep
{
    public function __construct(private readonly SecureFileStorage $files, private readonly RecordConsent $consent) {}

    /** @return array<string, mixed> */
    public static function rules(int $step): array
    {
        return match ($step) {
            1 => ['trade_name' => ['required', 'string', 'max:150'], 'legal_name' => ['nullable', 'string', 'max:200']],
            2 => [
                'registration_number' => ['nullable', 'string', 'max:40'], 'founded_year' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
                'website' => ['nullable', 'url', 'max:200'], 'description' => ['nullable', 'string', 'max:3000'], 'products_services' => ['nullable', 'string', 'max:2000'],
            ],
            3 => ['industry' => ['required', Rule::in(config('platform.industries'))]],
            4 => ['size' => ['required', Rule::in(config('platform.company_sizes'))], 'employees_range' => ['required', Rule::in(config('platform.employee_ranges'))]],
            5 => ['country' => ['required', Rule::in(config('platform.countries'))], 'province' => ['nullable', 'string', 'max:80'], 'city' => ['nullable', 'string', 'max:80'], 'address' => ['nullable', 'string', 'max:500']],
            6 => [
                'contact_name' => ['required', 'string', 'max:120'], 'contact_email' => ['required', 'email', 'max:150'],
                'contact_phone' => ['required', 'string', 'max:30'], 'preferred_language' => ['required', Rule::in(config('platform.locales'))],
            ],
            7 => ['main_needs' => ['required', 'array', 'min:1'], 'main_needs.*' => [Rule::in(config('platform.main_needs'))]],
            8 => ['documents' => ['nullable', 'array', 'max:10'], 'documents.*' => SecureFileStorage::documentRules(), 'document_type' => ['nullable', 'string', 'max:40']],
            9 => [
                'privacy' => ['required', 'array'], 'privacy.*' => [Rule::in(PrivacySetting::LEVELS)],
                'consent_data_processing' => ['accepted'], 'consent_ai_processing' => ['accepted'],
            ],
            default => [],
        };
    }

    /** @param array<string, mixed> $input */
    public function handle(Business $business, User $user, int $step, array $input): Business
    {
        $data = Validator::make($input, self::rules($step))->validate();

        match ($step) {
            8 => collect($data['documents'] ?? [])->each(function ($file) use ($business, $user, $data) {
                $doc = BusinessDocument::create($this->files->store($file, "businesses/{$business->id}") + [
                    'business_id' => $business->id, 'uploaded_by' => $user->id, 'type' => $data['document_type'] ?? 'registration',
                ]);
                $this->files->scan($doc);
            }),
            9 => $this->savePrivacy($business, $user, $data),
            default => $business->fill($data),
        };

        $business->onboarding_step = max($business->onboarding_step, min($step + 1, Business::ONBOARDING_STEPS));
        if ($step === Business::ONBOARDING_STEPS) {
            $business->onboarding_completed_at ??= now();
        }
        $business->save();
        if ($step === Business::ONBOARDING_STEPS && is_null($business->eligibility_status)) {
            app(EvaluateEligibility::class)->handle($business);
        }

        return $business;
    }

    private function savePrivacy(Business $business, User $user, array $data): void
    {
        $business->syncPrivacy($data['privacy']);
        $this->consent->handle($user, 'data_processing', true);
        $this->consent->handle($user, 'ai_processing', true);
    }
}
