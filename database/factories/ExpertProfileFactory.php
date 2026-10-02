<?php

namespace Database\Factories;

use App\Domain\Experts\Enums\ExpertVerificationStatus;
use App\Domain\Experts\Models\ExpertProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExpertProfile> */
class ExpertProfileFactory extends Factory
{
    protected $model = ExpertProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'headline' => fake()->jobTitle(),
            'bio' => fake()->paragraph(),
            'country' => 'IR',
            'timezone' => 'Asia/Tehran',
            'years_experience' => fake()->numberBetween(3, 25),
            'industries' => ['manufacturing'],
            'serves_countries' => ['IR'],
            'collaboration_types' => ['consultation'],
            'max_active_cases' => 5,
            'is_available' => true,
            'verification_status' => ExpertVerificationStatus::Verified,
            'verified_at' => now(),
            'nda_accepted_at' => now(),
            'nda_version' => '2026-10',
        ];
    }
}
