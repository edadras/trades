<?php

namespace Tests\Feature;

use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Experts\Enums\ExpertVerificationStatus;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use Tests\TestCase;

class ExpertNetworkTest extends TestCase
{
    public function test_expert_applies_and_is_verified(): void
    {
        $user = User::factory()->create();
        $energy = CaseCategory::where('slug', 'energy')->first();

        $this->actingAs($user)->put('/en/expert/profile', [
            'headline' => 'Energy auditor', 'bio' => 'Ten years of industrial energy audits.', 'country' => 'IR', 'timezone' => 'Asia/Tehran',
            'years_experience' => 10, 'industries' => ['food'], 'serves_countries' => ['IR'], 'collaboration_types' => ['consultation'],
            'max_active_cases' => 4, 'is_available' => true, 'supporter_type' => 'individual', 'support_models' => ['voluntary', 'free'],
            'skills' => [['case_category_id' => $energy->id, 'level' => 5, 'years' => 10]],
            'languages' => [['language' => 'fa', 'proficiency' => 'native']],
            'availability' => [['weekday' => 1, 'starts_at' => '09:00', 'ends_at' => '12:00']],
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->post('/en/expert/profile/submit', ['nda' => false])->assertSessionHasErrors('nda');
        $this->actingAs($user)->post('/en/expert/profile/submit', ['nda' => true])->assertSessionHasNoErrors();
        $profile = $user->fresh()->expertProfile;
        $this->assertSame(ExpertVerificationStatus::Submitted, $profile->verification_status);
        $this->assertNotNull($profile->nda_accepted_at);

        $ops = $this->staff(Role::OperationsManager);
        $this->actingAs($ops)->post("/en/admin/experts/{$profile->id}/verify", ['status' => 'verified', 'checklist' => ['identity' => true]])->assertSessionHasNoErrors();

        $this->assertTrue($profile->fresh()->isVerified());
        $this->assertTrue($user->fresh()->hasRole('supporter'));
        $this->actingAs($user->fresh())->get('/en/expert/dashboard')->assertOk();
    }

    public function test_public_directory_only_lists_verified_experts(): void
    {
        $this->expert();
        $this->expert([], ['verification_status' => ExpertVerificationStatus::Submitted, 'headline' => 'Hidden applicant']);

        $this->get('/en/experts')->assertOk()->assertDontSee('Hidden applicant');
    }
}
