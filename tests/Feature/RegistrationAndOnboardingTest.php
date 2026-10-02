<?php

namespace Tests\Feature;

use App\Models\Consent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class RegistrationAndOnboardingTest extends TestCase
{
    public function test_business_registers_and_completes_the_wizard(): void
    {
        $this->post('/fa/register', [
            'account_type' => 'business', 'name' => 'Sara Ahmadi', 'company_name' => 'Ahmadi Foods', 'email' => 'sara@example.com',
            'password' => 'Secret12345', 'password_confirmation' => 'Secret12345', 'terms' => true,
        ])->assertRedirect();

        $user = User::where('email', 'sara@example.com')->first();
        $this->assertTrue($user->hasRole('business'));
        $this->assertSame(2, Consent::where('user_id', $user->id)->count());
        $user->markEmailAsVerified();

        $this->actingAs($user)->get('/fa/dashboard')->assertRedirect(route('onboarding.show', ['locale' => 'fa']));

        $steps = [
            1 => ['trade_name' => 'Ahmadi Foods'],
            2 => ['founded_year' => 2015, 'website' => 'https://ahmadi.example', 'registration_number' => '1234567890'],
            3 => ['industry' => 'food'],
            4 => ['size' => 'small', 'employees_range' => '10-49'],
            5 => ['country' => 'IR', 'province' => 'tehran', 'city' => 'Tehran'],
            6 => ['contact_name' => 'Sara', 'contact_email' => 'sara@example.com', 'contact_phone' => '09120000000', 'preferred_language' => 'fa'],
            7 => ['main_needs' => ['energy', 'finance']],
            8 => ['documents' => [UploadedFile::fake()->create('license.pdf', 100, 'application/pdf')], 'document_type' => 'license'],
            9 => ['privacy' => ['contact_phone' => 'private'], 'consent_data_processing' => true, 'consent_ai_processing' => true],
        ];
        foreach ($steps as $step => $data) {
            $this->actingAs($user)->post('/fa/onboarding', ['step' => $step] + $data)->assertSessionHasNoErrors();
        }

        $business = $user->currentBusiness();
        $this->assertTrue($business->isOnboarded());
        $this->assertSame('1234567890', $business->registration_number);
        $this->assertNotSame('1234567890', \DB::table('businesses')->value('registration_number'), 'sensitive fields are encrypted at rest');
        $this->assertSame('private', $business->privacyMap()['contact_phone']);
        $this->assertSame(1, $business->documents()->count());
        $this->actingAs($user)->get('/fa/dashboard')->assertOk();
    }

    public function test_wizard_validates_each_step(): void
    {
        $user = $this->businessUser();
        $this->actingAs($user)->post('/fa/onboarding', ['step' => 3, 'industry' => 'spaceships'])->assertSessionHasErrors('industry');
        $this->actingAs($user)->post('/fa/onboarding', ['step' => 9, 'privacy' => []])->assertSessionHasErrors(['consent_data_processing', 'consent_ai_processing']);
    }

    public function test_supporter_registration_leads_to_expert_application(): void
    {
        $this->post('/en/register', [
            'account_type' => 'supporter', 'name' => 'Expert One', 'email' => 'ex@example.com',
            'password' => 'Secret12345', 'password_confirmation' => 'Secret12345', 'terms' => true,
        ]);
        $user = User::where('email', 'ex@example.com')->first();
        $this->assertFalse($user->hasRole('supporter'), 'supporter role is only granted after verification');
        $this->assertSame('expert.profile.edit', $user->homeRouteName());
    }
}
