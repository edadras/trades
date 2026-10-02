<?php

namespace Database\Factories;

use App\Domain\Business\Models\Business;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SupportCase> */
class SupportCaseFactory extends Factory
{
    protected $model = SupportCase::class;

    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'locale' => 'fa',
            'status' => CaseStatus::Draft,
        ];
    }
}
