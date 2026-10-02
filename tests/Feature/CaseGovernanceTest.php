<?php

namespace Tests\Feature;

use App\Domain\AI\Models\AiHumanReview;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Compliance\Models\CollaborationRequest;
use App\Domain\Compliance\Models\ServicePath;
use App\Domain\Identity\Enums\Role;
use App\Domain\Matching\Enums\MatchStatus;
use Tests\TestCase;

class CaseGovernanceTest extends TestCase
{
    public function test_without_ai_consent_the_case_goes_straight_to_human_review(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $case = $this->submitCase($business, self::ENERGY_PROBLEM, ['consent_ai_processing' => false, 'actions_taken' => 'Reduced night shifts']);

        $this->assertSame(CaseStatus::HumanReview, $case->status);
        $this->assertSame('no_ai_consent', AiHumanReview::where('case_id', $case->id)->value('reason'));
        $this->assertNull($case->latestAnalysis, 'no AI analysis is stored without consent');
        $this->assertSame('Reduced night shifts', $case->actions_taken);
        $this->assertDatabaseHas('consents', ['user_id' => $business->id, 'type' => 'case_ai_processing', 'granted' => false]);
    }

    public function test_foreign_experts_are_not_matched_without_cross_border_consent(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $domestic = $this->expert();
        $abroad = $this->expert(['energy', 'energy-high-consumption'], ['country' => 'DE', 'serves_countries' => ['IR']]);

        $case = $this->submitCase($business, self::ENERGY_PROBLEM, ['consent_share_with_foreign_experts' => false]);

        $this->assertTrue($case->matches()->where('expert_profile_id', $domestic->id)->exists());
        $this->assertFalse($case->matches()->where('expert_profile_id', $abroad->id)->exists());
    }

    public function test_sensitive_case_with_expert_abroad_hides_confidential_data_until_legal_approval(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $abroad = $this->expert(['energy', 'energy-high-consumption'], ['country' => 'DE', 'serves_countries' => ['IR'], 'support_models' => ['free', 'commercial']]);
        $case = $this->submitCase($business);
        $case->update(['is_sensitive' => true]);

        $case = $this->engage($case, $abroad, 'commercial', 'Monthly fee');

        $requests = CollaborationRequest::where('case_id', $case->id)->with('servicePath')->get();
        $this->assertEqualsCanonicalizing(['commercial_contract', 'cross_border_data'], $requests->pluck('servicePath.key')->all());
        $this->assertTrue($requests->every(fn ($r) => $r->status === 'pending'));
        $this->assertFalse($abroad->user->can('viewConfidential', $case));
        $this->actingAs($abroad->user)->get("/fa/expert/cases/{$case->number}")->assertOk()
            ->assertInertia(fn ($page) => $page->where('case.confidential_access', false)->missing('case.business.contact_email'));

        $legal = $this->staff(Role::LegalCompliance);
        $crossBorder = $requests->firstWhere('servicePath.key', 'cross_border_data');
        $this->actingAs($legal)->post("/fa/admin/compliance/requests/{$crossBorder->id}", ['approve' => true, 'conditions' => 'Only energy bills'])->assertSessionHasNoErrors();

        $this->assertTrue($abroad->user->fresh()->can('viewConfidential', $case->fresh()));
        $this->actingAs($legal)->post("/fa/admin/compliance/requests/{$crossBorder->id}", ['approve' => false])->assertSessionHasErrors();
    }

    public function test_service_path_modes_control_collaboration_requests(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();
        $case = $this->engage($this->submitCase($business), $expert);
        $mentoring = ServicePath::where('key', 'mentoring')->first();
        $investment = ServicePath::where('key', 'investment')->first();

        $this->actingAs($business)->post("/fa/cases/{$case->number}/collaboration", ['service_path_id' => $mentoring->id, 'description' => 'Monthly mentoring sessions'])->assertSessionHasNoErrors();
        $this->assertSame('approved', CollaborationRequest::where('service_path_id', $mentoring->id)->value('status'));

        $this->actingAs($business)->post("/fa/cases/{$case->number}/collaboration", ['service_path_id' => $investment->id, 'description' => 'Expert offered to invest in the line'])->assertSessionHasNoErrors();
        $this->assertSame('pending', CollaborationRequest::where('service_path_id', $investment->id)->value('status'));

        $investment->update(['mode' => 'disabled']);
        $fund = ServicePath::where('key', 'fund_transfer')->first();
        $fund->update(['mode' => 'disabled']);
        $this->actingAs($business)->post("/fa/cases/{$case->number}/collaboration", ['service_path_id' => $fund->id, 'description' => 'Pay the expert directly'])->assertSessionHasErrors('service_path_id');

        $outsider = $this->businessUser();
        $this->actingAs($outsider)->post("/fa/cases/{$case->number}/collaboration", ['service_path_id' => $mentoring->id, 'description' => 'Monthly mentoring sessions'])->assertForbidden();
    }

    public function test_expert_leaving_returns_the_case_to_matching(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();
        $other = $this->expert();
        $case = $this->engage($this->submitCase($business), $expert);

        $this->actingAs($expert->user)->post("/fa/cases/{$case->number}/leave", ['reason' => 'No longer available this month'])->assertRedirect();

        $case->refresh();
        $this->assertFalse($case->hasActiveExpert($expert->user));
        $this->assertDatabaseHas('case_experts', ['case_id' => $case->id, 'expert_profile_id' => $expert->id, 'leave_reason' => 'No longer available this month']);
        $this->assertContains($case->status, [CaseStatus::Matching, CaseStatus::ExpertProposed]);
        $this->assertTrue($case->matches()->where('expert_profile_id', $other->id)->where('status', MatchStatus::Proposed->value)->exists(), 'matching runs again');
        $this->actingAs($expert->user)->get("/fa/expert/cases/{$case->number}")->assertForbidden();
    }

    public function test_business_requests_a_replacement_and_outsiders_cannot(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();
        $case = $this->engage($this->submitCase($business), $expert);

        $this->actingAs($this->businessUser())->post("/fa/cases/{$case->number}/experts/{$expert->id}/release", ['reason' => 'Not responsive'])->assertForbidden();
        $this->actingAs($business)->post("/fa/cases/{$case->number}/experts/{$expert->id}/release", ['reason' => 'Not responsive enough'])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('case_experts', ['case_id' => $case->id, 'expert_profile_id' => $expert->id, 'status' => 'removed', 'leave_reason' => 'Not responsive enough']);
        $this->assertTrue($case->events()->where('type', 'expert_replacement_requested')->exists());
        $this->assertFalse($case->fresh()->matches()->where('expert_profile_id', $expert->id)->where('status', MatchStatus::Proposed->value)->exists(), 'the released expert is not proposed again');
    }
}
