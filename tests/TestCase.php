<?php

namespace Tests;

use App\Domain\Business\Models\Business;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use Database\Seeders\KnowledgeSeeder;
use Database\Seeders\KpiSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\ServicePathSeeder;
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
        $this->seed([RolesAndPermissionsSeeder::class, TaxonomySeeder::class, KnowledgeSeeder::class, KpiSeeder::class, ServicePathSeeder::class]);
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

    protected const ENERGY_PROBLEM = 'هزینه انرژی کارخانه ما طی سه ماه گذشته حدود ۳۰٪ افزایش یافته و نمی‌دانیم مشکل از تجهیزات است یا الگوی مصرف. قبض برق بالا رفته.';

    /** Creates, interviews and submits a case through HTTP, like a business would. */
    protected function submitCase(User $business, string $text = self::ENERGY_PROBLEM, array $fields = []): SupportCase
    {
        $this->actingAs($business)->post('/fa/cases', ['description' => $text] + $fields)->assertSessionHasNoErrors()->assertRedirect();
        $case = SupportCase::latest('id')->first();
        if ($question = $case->answers()->whereNull('answer')->first()) {
            $this->actingAs($business)->post("/fa/cases/{$case->number}/intake", ['key' => $question->question_key, 'answer' => 'از سه ماه پیش، حدود ۳۰ درصد'])->assertRedirect();
        }
        $this->actingAs($business)->post("/fa/cases/{$case->number}/submit")->assertRedirect();

        return $case->fresh();
    }

    /** The business accepts the proposed expert and the expert accepts the invitation. */
    protected function engage(SupportCase $case, ExpertProfile $expert, string $model = 'free', ?string $terms = null): SupportCase
    {
        $match = $case->matches()->where('expert_profile_id', $expert->id)->firstOrFail();
        $this->actingAs($case->business->owner)->post("/fa/cases/{$case->number}/matches/{$match->id}", ['accept' => true])->assertSessionHasNoErrors();
        $this->actingAs($expert->user)->post("/fa/expert/invitations/{$match->id}", ['accept' => true, 'engagement_model' => $model, 'engagement_terms' => $terms])->assertSessionHasNoErrors()->assertRedirect();

        return $case->fresh();
    }
}
