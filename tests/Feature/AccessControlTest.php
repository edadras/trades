<?php

namespace Tests\Feature;

use App\Domain\Business\Actions\ManageTeam;
use App\Domain\Cases\CaseNotifier;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Identity\Enums\Role;
use App\Domain\Matching\Enums\MatchStatus;
use App\Models\User;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    public function test_suspended_users_cannot_sign_in_and_are_signed_out(): void
    {
        $user = $this->businessUser();
        $user->update(['status' => 'suspended']);

        $this->post('/en/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user->fresh())->get('/en/dashboard')->assertRedirect('/en/login');
        $this->assertGuest();
    }

    public function test_staff_who_can_view_all_cases_open_them_from_notifications(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $case = $this->submitCase($business);

        foreach ([Role::LegalCompliance, Role::ProgramLead, Role::NetworkManager] as $role) {
            $staff = $this->staff($role);
            $url = app(CaseNotifier::class)->caseUrlFor($staff, $case);
            $this->actingAs($staff)->get($url)->assertOk()->assertInertia(fn ($page) => $page->component('Cases/Show')->where('case.can.review', false));
        }
        $this->actingAs($this->staff(Role::ContentManager))->get("/fa/review/cases/{$case->number}")->assertForbidden();
    }

    public function test_team_members_follow_cases_but_owners_and_admins_take_decisions(): void
    {
        $owner = $this->businessUser(['industry' => 'food']);
        $business = $owner->currentBusiness();
        $member = User::factory()->create();
        $admin = User::factory()->create();
        $team = app(ManageTeam::class);
        $team->accept($business->invitations()->create(['email' => $member->email, 'role' => 'member', 'token_hash' => 'a', 'invited_by' => $owner->id, 'expires_at' => now()->addDay()]), $member);
        $team->accept($business->invitations()->create(['email' => $admin->email, 'role' => 'admin', 'token_hash' => 'b', 'invited_by' => $owner->id, 'expires_at' => now()->addDay()]), $admin);
        $expert = $this->expert();
        $case = $this->engage($this->submitCase($owner), $expert);

        $this->assertTrue($case->conversation->hasMember($member->fresh()), 'members joining later get the case chat');
        $this->actingAs($member->fresh())->post("/fa/cases/{$case->number}/messages", ['body' => 'Uploading the bills today'])->assertRedirect();
        $this->actingAs($member->fresh())->put('/fa/business/profile', ['trade_name' => 'X'])->assertForbidden();

        $this->actingAs($expert->user)->post("/fa/cases/{$case->number}/outcome", ['outcome' => 'resolved', 'reason' => 'Leaks repaired'])->assertSessionHasNoErrors();
        $this->actingAs($member->fresh())->post("/fa/cases/{$case->number}/outcome/confirm", ['confirm' => true])->assertForbidden();
        $this->actingAs($admin->fresh())->post("/fa/cases/{$case->number}/outcome/confirm", ['confirm' => true])->assertRedirect();
        $this->assertSame(CaseStatus::Resolved, $case->fresh()->status);
    }

    public function test_removed_team_member_loses_case_chat_and_lands_on_a_working_page(): void
    {
        $owner = $this->businessUser(['industry' => 'food']);
        $business = $owner->currentBusiness();
        $member = User::factory()->create();
        app(ManageTeam::class)->accept($business->invitations()->create(['email' => $member->email, 'role' => 'member', 'token_hash' => 'c', 'invited_by' => $owner->id, 'expires_at' => now()->addDay()]), $member);
        $expert = $this->expert();
        $case = $this->engage($this->submitCase($owner), $expert);
        $this->assertTrue($case->conversation->hasMember($member));

        $this->actingAs($owner)->delete("/fa/business/team/{$member->id}")->assertRedirect();

        $member->refresh();
        $this->assertFalse($case->conversation->fresh()->hasMember($member));
        $this->assertFalse($member->hasRole(Role::Business->value));
        $this->actingAs($member)->get("/fa/cases/{$case->number}")->assertForbidden();
        $this->assertNotSame('onboarding.show', $member->homeRouteName());
    }

    public function test_users_in_several_businesses_can_switch_between_them(): void
    {
        $first = $this->businessUser(['trade_name' => 'First Co']);
        $secondOwner = $this->businessUser(['trade_name' => 'Second Co']);
        $second = $secondOwner->currentBusiness();
        app(ManageTeam::class)->accept($second->invitations()->create(['email' => $first->email, 'role' => 'admin', 'token_hash' => 'd', 'invited_by' => $secondOwner->id, 'expires_at' => now()->addDay()]), $first);

        $this->actingAs($first)->get('/fa/dashboard')->assertInertia(fn ($page) => $page->where('auth.user.business.name', 'First Co')->has('auth.user.businesses', 2));
        $this->actingAs($first)->post('/fa/business/switch', ['business_id' => $second->id])->assertRedirect();
        $this->actingAs($first)->get('/fa/dashboard')->assertInertia(fn ($page) => $page->where('auth.user.business.name', 'Second Co'));
        $this->actingAs($first)->post('/fa/business/switch', ['business_id' => $this->businessUser()->currentBusiness()->id])->assertForbidden();
    }

    public function test_case_staff_can_reopen_a_closed_case(): void
    {
        $owner = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();
        $case = $this->engage($this->submitCase($owner), $expert);
        $this->actingAs($owner)->post("/fa/cases/{$case->number}/outcome", ['outcome' => 'resolved', 'reason' => 'Leaks repaired']);
        $this->actingAs($owner)->post("/fa/cases/{$case->number}/close");

        $this->actingAs($this->staff(Role::CaseExpert))->post("/fa/cases/{$case->number}/reopen", ['reason' => 'Business called: costs are back up'])->assertRedirect();
        $this->assertSame(CaseStatus::InProgress, $case->fresh()->status);
    }

    public function test_suspending_a_supporter_removes_them_from_cases_and_invitations(): void
    {
        $owner = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();
        $other = $this->expert();
        $case = $this->engage($this->submitCase($owner), $expert);
        $admin = $this->staff(Role::Admin);

        $this->actingAs($admin)->post("/fa/admin/experts/{$expert->id}/verify", ['status' => 'suspended', 'notes' => 'Complaint under review'])->assertRedirect();

        $this->assertFalse($case->fresh()->hasActiveExpert($expert->user));
        $this->assertFalse($expert->user->fresh()->hasRole(Role::Supporter->value));
        $this->assertFalse($case->fresh()->matches()->where('expert_profile_id', $expert->id)->where('status', MatchStatus::Proposed->value)->exists());
        $this->assertTrue($case->fresh()->matches()->where('expert_profile_id', $other->id)->exists(), 'the case is matched again');
    }

    public function test_knowledge_reviewers_cannot_author_articles(): void
    {
        $legal = $this->staff(Role::LegalCompliance);
        $this->actingAs($legal)->get('/fa/admin/knowledge')->assertOk();
        $this->actingAs($legal)->get('/fa/admin/knowledge/create')->assertForbidden();
        $this->actingAs($this->staff(Role::ContentManager))->get('/fa/admin/knowledge/create')->assertOk();
    }

    public function test_registration_with_an_invalid_invitation_is_rejected_cleanly(): void
    {
        $this->post('/en/register', [
            'account_type' => 'business', 'name' => 'Someone', 'email' => 'someone@example.com', 'invitation' => str_repeat('x', 48),
            'password' => 'Secret12345', 'password_confirmation' => 'Secret12345', 'terms' => true,
        ])->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['email' => 'someone@example.com']);
    }
}
