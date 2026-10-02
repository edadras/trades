<?php

namespace Tests;

use App\Domain\Business\Models\Business;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use Database\Seeders\KnowledgeSeeder;
use Database\Seeders\KpiSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesAndPermissionsSeeder::class, TaxonomySeeder::class, KnowledgeSeeder::class, KpiSeeder::class]);
        Storage::fake('private');
        app()->setLocale('fa');
    }

    protected function businessUser(array $business = []): User
    {
        $user = User::factory()->create(['locale' => 'fa']);
        $user->assignRole(Role::Business->value);
        Business::factory()->create(['owner_id' => $user->id] + $business);

        return $user->fresh();
    }

    protected function staff(Role $role = Role::CaseExpert): User
    {
        $user = User::factory()->create(['locale' => 'fa']);
        $user->assignRole($role->value);

        return $user;
    }

    /** @param array<int, string> $skills category slugs */
    protected function expert(array $skills = ['energy', 'energy-high-consumption'], array $attributes = []): ExpertProfile
    {
        $user = User::factory()->create(['locale' => 'fa']);
        $user->assignRole(Role::Supporter->value);
        $profile = ExpertProfile::factory()->create(['user_id' => $user->id, 'industries' => ['food', 'manufacturing']] + $attributes);
        foreach (CaseCategory::whereIn('slug', $skills)->get() as $category) {
            $profile->skills()->create(['case_category_id' => $category->id, 'level' => 4, 'years' => 8]);
        }
        $profile->languages()->create(['language' => 'fa', 'proficiency' => 'native']);
        $profile->availability()->create(['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00']);

        return $profile->fresh();
    }
}
