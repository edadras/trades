<?php

namespace Database\Factories;

use App\Domain\Business\Models\Business;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Business> */
class BusinessFactory extends Factory
{
    protected $model = Business::class;

    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'trade_name' => fake()->company(),
            'legal_name' => fake()->company().' Ltd.',
            'registration_number' => (string) fake()->numerify('##########'),
            'country' => 'IR',
            'province' => fake()->randomElement(['tehran', 'isfahan', 'khorasan_razavi', 'fars']),
            'city' => fake()->city(),
            'industry' => fake()->randomElement(['manufacturing', 'food', 'textile', 'it_software']),
            'employees_range' => '10-49',
            'size' => 'small',
            'founded_year' => fake()->numberBetween(1995, 2022),
            'preferred_language' => 'fa',
            'contact_name' => fake()->name(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->numerify('0912#######'),
            'main_needs' => ['finance'],
            'onboarding_step' => Business::ONBOARDING_STEPS,
            'onboarding_completed_at' => now(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (Business $b) => $b->members()->syncWithoutDetaching([$b->owner_id => ['role' => 'owner']]));
    }

    public function notOnboarded(): static
    {
        return $this->state(['onboarding_step' => 1, 'onboarding_completed_at' => null, 'industry' => null]);
    }
}
