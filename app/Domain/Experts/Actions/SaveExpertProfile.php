<?php

namespace App\Domain\Experts\Actions;

use App\Domain\Experts\Enums\ExpertVerificationStatus;
use App\Domain\Experts\Models\ExpertProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaveExpertProfile
{
    /** @return array<string, mixed> */
    public static function rules(): array
    {
        return [
            'headline' => ['required', 'string', 'max:160'],
            'bio' => ['required', 'string', 'max:4000'],
            'country' => ['required', Rule::in(config('platform.countries'))],
            'city' => ['nullable', 'string', 'max:80'],
            'timezone' => ['required', 'timezone'],
            'years_experience' => ['required', 'integer', 'min:0', 'max:60'],
            'industries' => ['array'], 'industries.*' => [Rule::in(config('platform.industries'))],
            'serves_countries' => ['array'], 'serves_countries.*' => [Rule::in(config('platform.countries'))],
            'collaboration_types' => ['array', 'min:1'], 'collaboration_types.*' => [Rule::in(ExpertProfile::COLLABORATION_TYPES)],
            'certifications' => ['array'], 'certifications.*.title' => ['required', 'string', 'max:160'], 'certifications.*.issuer' => ['nullable', 'string', 'max:160'], 'certifications.*.year' => ['nullable', 'integer'],
            'linkedin_url' => ['nullable', 'url', 'max:200'],
            'max_active_cases' => ['required', 'integer', 'min:1', 'max:30'],
            'is_available' => ['boolean'],
            'skills' => ['required', 'array', 'min:1'], 'skills.*.case_category_id' => ['required', 'exists:case_categories,id'],
            'skills.*.level' => ['required', 'integer', 'min:1', 'max:5'], 'skills.*.years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'languages' => ['required', 'array', 'min:1'], 'languages.*.language' => ['required', 'string', 'size:2'], 'languages.*.proficiency' => ['required', Rule::in(['native', 'fluent', 'professional', 'basic'])],
            'availability' => ['array'], 'availability.*.weekday' => ['required', 'integer', 'between:0,6'],
            'availability.*.starts_at' => ['required', 'date_format:H:i'], 'availability.*.ends_at' => ['required', 'date_format:H:i', 'after:availability.*.starts_at'],
        ];
    }

    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): ExpertProfile
    {
        return DB::transaction(function () use ($user, $data) {
            $profile = ExpertProfile::firstOrNew(['user_id' => $user->id]);
            $profile->fill(collect($data)->except(['skills', 'languages', 'availability'])->all());
            if (! $profile->exists) {
                $profile->verification_status = ExpertVerificationStatus::Draft;
            }
            $profile->save();

            $profile->skills()->delete();
            foreach ($data['skills'] as $skill) {
                $profile->skills()->create(['case_category_id' => $skill['case_category_id'], 'level' => $skill['level'], 'years' => $skill['years'] ?? 0]);
            }
            $profile->languages()->delete();
            foreach (collect($data['languages'])->unique('language') as $lang) {
                $profile->languages()->create($lang);
            }
            $profile->availability()->delete();
            foreach ($data['availability'] ?? [] as $slot) {
                $profile->availability()->create($slot);
            }

            return $profile->fresh(['skills', 'languages', 'availability']);
        });
    }
}
