<?php

namespace Tests\Feature;

use App\Domain\Analytics\MetricCalculator;
use App\Domain\Business\Models\Business;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Identity\Enums\Role;
use App\Domain\Pilot\Actions\EvaluateEligibility;
use App\Domain\Pilot\Models\DecisionGate;
use App\Domain\Pilot\Models\PilotProgram;
use App\Domain\Pilot\Models\PilotReport;
use Database\Seeders\PilotProgramSeeder;
use Tests\TestCase;

class PilotGovernanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PilotProgramSeeder::class);
    }

    public function test_eligibility_follows_pilot_scope_and_capacity(): void
    {
        $evaluate = app(EvaluateEligibility::class);
        $inScope = Business::factory()->create(['province' => 'tehran', 'industry' => 'food', 'size' => 'small']);
        $wrongRegion = Business::factory()->create(['province' => 'gilan', 'industry' => 'food', 'size' => 'small']);
        $wrongChain = Business::factory()->create(['province' => 'tehran', 'industry' => 'retail', 'size' => 'large']);

        $this->assertSame('eligible', $evaluate->handle($inScope)->eligibility_status);
        $this->assertSame('ineligible', $evaluate->handle($wrongRegion)->eligibility_status);
        $this->assertSame('industry,size', $evaluate->handle($wrongChain)->eligibility_reason);

        PilotProgram::active()->update(['max_businesses' => 1]);
        $late = Business::factory()->create(['province' => 'isfahan', 'industry' => 'textile', 'size' => 'medium']);
        $this->assertSame('waitlisted', $evaluate->handle($late)->eligibility_status);
    }

    public function test_ineligible_business_cannot_open_cases_until_overridden(): void
    {
        $user = $this->businessUser(['province' => 'gilan', 'industry' => 'food']);
        $business = $user->currentBusiness();
        app(EvaluateEligibility::class)->handle($business);

        $this->actingAs($user)->post('/fa/cases', ['description' => 'هزینه انرژی کارخانه ما بالا رفته است و قبض برق دو برابر شده.'])->assertForbidden();

        $lead = $this->staff(Role::ProgramLead);
        $this->actingAs($lead)->post("/fa/admin/businesses/{$business->id}/eligibility", ['eligibility_status' => 'eligible', 'eligibility_reason' => 'Strategic partner'])->assertSessionHasNoErrors();
        $this->assertSame('override: Strategic partner', $business->fresh()->eligibility_reason);
        $this->actingAs($user)->post('/fa/cases', ['description' => 'هزینه انرژی کارخانه ما بالا رفته است و قبض برق دو برابر شده.'])->assertRedirect();
        $this->assertSame(1, SupportCase::count());
    }

    public function test_pilot_scope_allows_at_most_two_value_chains(): void
    {
        $lead = $this->staff(Role::ProgramLead);
        $payload = ['name' => ['fa' => 'پایلوت', 'en' => 'Pilot'], 'status' => 'active', 'max_groups' => 2, 'regions' => ['tehran'], 'value_chains' => ['agri_food', 'industrial', 'energy']];

        $this->actingAs($lead)->put('/fa/admin/pilot', $payload)->assertSessionHasErrors('value_chains');
        $this->actingAs($lead)->put('/fa/admin/pilot', ['value_chains' => ['agri_food', 'energy']] + $payload)->assertSessionHasNoErrors();
        $this->assertSame(['agri_food', 'energy'], PilotProgram::active()->value_chains);
        $this->actingAs($this->staff(Role::ContentManager))->get('/fa/admin/pilot')->assertForbidden();
    }

    public function test_negative_gate_decision_requires_a_root_cause_and_captures_kpi_evidence(): void
    {
        $lead = $this->staff(Role::ProgramLead);
        $gate = DecisionGate::where('key', 'launch')->firstOrFail();
        $this->assertSame(16, $gate->week);

        $this->actingAs($lead)->post("/fa/admin/pilot/gates/{$gate->id}", ['decision' => 'postpone'])->assertSessionHasErrors('root_cause');
        $this->actingAs($lead)->post("/fa/admin/pilot/gates/{$gate->id}", ['decision' => 'postpone', 'root_cause' => 'supporter_shortage', 'notes' => 'Only 9 verified supporters'])->assertSessionHasNoErrors()->assertRedirect();

        $gate->refresh();
        $this->assertSame('supporter_shortage', $gate->root_cause);
        $this->assertSame($lead->id, $gate->decided_by);
        $this->assertNotEmpty($gate->evidence['kpis']);
        $this->actingAs($lead)->post("/fa/admin/pilot/gates/{$gate->id}", ['decision' => 'scale'])->assertSessionHasErrors('decision');
    }

    public function test_weekly_report_collects_results_and_errors(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $this->submitCase($business, 'اوضاع کسب‌وکار این روزها خوب نیست.');
        $lead = $this->staff(Role::ProgramLead);

        $this->actingAs($lead)->post('/fa/admin/pilot/reports')->assertRedirect();
        $report = PilotReport::firstOrFail();
        $this->assertSame(1, $report->metrics['cases_submitted']);
        $this->assertArrayHasKey('sla_breaches', $report->errors);

        $this->actingAs($lead)->get("/fa/admin/pilot/reports/{$report->id}")->assertOk();
        $this->actingAs($lead)->get("/fa/admin/pilot/reports/{$report->id}/export")->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->artisan('pilot:weekly-report')->assertSuccessful();
    }

    public function test_initial_review_kpi_is_the_share_reviewed_within_48_hours(): void
    {
        $business = $this->businessUser()->currentBusiness();
        SupportCase::factory()->for($business)->create(['status' => 'ready', 'submitted_at' => now()->subDays(5), 'ready_at' => now()->subDays(5)->addHours(3)]);
        SupportCase::factory()->for($business)->create(['status' => 'ready', 'submitted_at' => now()->subDays(5), 'ready_at' => now()->subDays(2)]);
        SupportCase::factory()->for($business)->create(['status' => 'human_review', 'submitted_at' => now()->subDays(4)]);
        SupportCase::factory()->for($business)->create(['status' => 'human_review', 'submitted_at' => now()->subHours(2)]);

        $this->assertSame(33.3, app(MetricCalculator::class)->initialReviewSlaRate(), 'open cases still within 48h are not counted yet');
    }
}
