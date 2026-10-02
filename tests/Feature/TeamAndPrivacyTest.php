<?php

namespace Tests\Feature;

use App\Domain\Business\Models\BusinessInvitation;
use App\Domain\Business\Models\Partner;
use App\Domain\Compliance\Models\Complaint;
use App\Domain\Compliance\Models\DataRequest;
use App\Domain\Identity\Enums\Role;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class TeamAndPrivacyTest extends TestCase
{
    private function invite(User $owner, string $email, string $role = 'member'): string
    {
        Notification::fake();
        $this->actingAs($owner)->post('/fa/business/team', ['email' => $email, 'role' => $role])->assertSessionHasNoErrors();
        $url = null;
        Notification::assertSentTo(new AnonymousNotifiable, TeamInvitationNotification::class, function ($n, $channels, $notifiable) use ($email, &$url) {
            $url = (fn () => $this->url)->call($n);

            return $notifiable->routes['mail'] === $email;
        });

        return basename(parse_url($url, PHP_URL_PATH));
    }

    public function test_invited_colleague_registers_and_joins_the_business(): void
    {
        $owner = $this->businessUser(['industry' => 'food']);
        $token = $this->invite($owner, 'colleague@example.com');
        Auth::logout();

        $this->get("/fa/invitations/{$token}")->assertOk()->assertInertia(fn ($page) => $page->component('Business/Invitation')->where('invitation.valid', true));

        $this->post('/fa/register', [
            'account_type' => 'business', 'name' => 'Colleague', 'email' => 'colleague@example.com', 'invitation' => $token,
            'password' => 'Secret12345', 'password_confirmation' => 'Secret12345', 'terms' => true,
        ])->assertRedirect();

        $colleague = User::where('email', 'colleague@example.com')->firstOrFail();
        $business = $owner->currentBusiness();
        $this->assertTrue($business->hasMember($colleague));
        $this->assertSame('member', $business->roleOf($colleague));
        $this->assertSame(1, $colleague->businesses()->count(), 'no second business is created');
        $this->assertNotNull(BusinessInvitation::first()->accepted_at);

        // Members follow cases but cannot manage the team.
        $this->actingAs($colleague->fresh())->post('/fa/business/team', ['email' => 'x@example.com', 'role' => 'member'])->assertForbidden();
    }

    public function test_existing_user_accepts_only_an_invitation_for_their_email(): void
    {
        $owner = $this->businessUser();
        $token = $this->invite($owner, 'partner@example.com', 'admin');
        $stranger = User::factory()->create(['email' => 'other@example.com']);
        $invitee = User::factory()->create(['email' => 'partner@example.com']);

        $this->actingAs($stranger)->post("/fa/invitations/{$token}")->assertSessionHasErrors('token');
        $this->actingAs($invitee)->post("/fa/invitations/{$token}")->assertRedirect();

        $this->assertSame('admin', $owner->currentBusiness()->roleOf($invitee));
        $this->assertTrue($invitee->fresh()->hasRole(Role::Business->value));
        $this->actingAs($invitee->fresh())->post("/fa/invitations/{$token}")->assertSessionHasErrors('token');
    }

    public function test_partner_referral_code_is_attached_to_the_new_business(): void
    {
        $partner = Partner::create(['name' => ['fa' => 'اتاق', 'en' => 'Chamber'], 'type' => 'chamber']);

        $this->post('/fa/register', [
            'account_type' => 'business', 'name' => 'Sara', 'company_name' => 'Sara Foods', 'email' => 'sara@example.com', 'ref' => strtolower($partner->referral_code),
            'password' => 'Secret12345', 'password_confirmation' => 'Secret12345', 'terms' => true,
        ])->assertRedirect();

        $this->assertSame($partner->id, User::where('email', 'sara@example.com')->first()->currentBusiness()->partner_id);
    }

    public function test_user_exports_their_data_through_a_signed_link(): void
    {
        $owner = $this->businessUser();
        $this->actingAs($owner)->post('/fa/settings/data', ['type' => 'export'])->assertSessionHasNoErrors();

        $request = DataRequest::firstOrFail();
        $this->assertSame('completed', $request->status, 'the export job ran');
        Storage::disk('private')->assertExists($request->file_path);

        $this->actingAs($owner)->get("/fa/settings/data/{$request->id}/download")->assertForbidden();
        $url = URL::temporarySignedRoute('settings.data.download', now()->addMinutes(10), ['locale' => 'fa', 'dataRequest' => $request->id]);
        $this->actingAs($this->businessUser())->get($url)->assertForbidden();
        $this->assertStringContainsString($owner->email, $this->actingAs($owner)->get($url)->assertOk()->streamedContent());
    }

    public function test_account_deletion_is_reviewed_and_anonymises_the_user(): void
    {
        $owner = $this->businessUser();
        $this->actingAs($owner)->post('/fa/settings/data', ['type' => 'delete'])->assertSessionHasErrors('password');
        $this->actingAs($owner)->post('/fa/settings/data', ['type' => 'delete', 'password' => 'password', 'reason' => 'Closing the company'])->assertSessionHasNoErrors();
        $request = DataRequest::firstOrFail();
        $this->assertSame('pending', $request->status);

        $legal = $this->staff(Role::LegalCompliance);
        $this->actingAs($legal)->post("/fa/admin/compliance/data-requests/{$request->id}", ['approve' => true, 'resolution' => 'Verified by phone'])->assertSessionHasNoErrors();

        $deleted = User::withTrashed()->find($owner->id);
        $this->assertSame('deleted-'.$owner->id.'@deleted.invalid', $deleted->email);
        $this->assertTrue($deleted->trashed());
        $this->assertSame('completed', $request->fresh()->status);
    }

    public function test_complaints_are_filed_and_resolved_with_notification(): void
    {
        $owner = $this->businessUser();
        $this->actingAs($owner)->post('/fa/support', ['category' => 'service_quality', 'subject' => 'Late reply', 'body' => 'The supporter replied after a week.'])->assertSessionHasNoErrors();
        $complaint = Complaint::firstOrFail();
        $this->assertSame('The supporter replied after a week.', $complaint->body);
        $this->assertNotSame('The supporter replied after a week.', DB::table('complaints')->value('body'), 'the body is encrypted at rest');

        $ops = $this->staff(Role::OperationsManager);
        $this->actingAs($ops)->put("/fa/admin/compliance/complaints/{$complaint->id}", ['status' => 'resolved'])->assertSessionHasErrors('resolution');
        $this->actingAs($ops)->put("/fa/admin/compliance/complaints/{$complaint->id}", ['status' => 'resolved', 'resolution' => 'Supporter coached on response times'])->assertSessionHasNoErrors();
        $this->assertSame('resolved', $complaint->fresh()->status);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $owner->id]);

        $this->actingAs($owner)->get('/fa/admin/compliance')->assertForbidden();
    }
}
