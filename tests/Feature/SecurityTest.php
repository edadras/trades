<?php

namespace Tests\Feature;

use App\Domain\Cases\Actions\CreateCase;
use App\Domain\Cases\Models\CaseDocument;
use App\Domain\Identity\Enums\Role;
use App\Models\AuditLog;
use App\Models\LoginCode;
use App\Notifications\LoginCodeNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    private function caseWithDocument(): array
    {
        $owner = $this->businessUser();
        $this->actingAs($owner);
        $case = app(CreateCase::class)->handle($owner, $owner->currentBusiness(), 'قبض برق', null, [UploadedFile::fake()->create('bill.pdf', 50, 'application/pdf')]);

        return [$owner, $case, CaseDocument::where('case_id', $case->id)->firstOrFail()];
    }

    public function test_files_are_private_and_need_a_signed_url(): void
    {
        [$owner, , $doc] = $this->caseWithDocument();
        $this->assertSame('skipped', $doc->fresh()->scan_status);

        $this->actingAs($owner)->get("/files/case/{$doc->id}")->assertForbidden(); // unsigned
        $this->actingAs($owner)->get($doc->fresh()->downloadUrl())->assertOk();
        $this->assertTrue(AuditLog::where('action', 'file.downloaded')->where('subject_id', $doc->id)->exists());
    }

    public function test_signed_url_is_useless_for_unauthorised_users(): void
    {
        [, , $doc] = $this->caseWithDocument();
        $stranger = $this->businessUser();

        $this->actingAs($stranger)->get($doc->fresh()->downloadUrl())->assertForbidden();
        auth()->logout();
        $this->assertContains($this->get($doc->fresh()->downloadUrl())->status(), [302, 403]);
    }

    public function test_infected_files_cannot_be_downloaded(): void
    {
        [$owner, , $doc] = $this->caseWithDocument();
        $doc->update(['scan_status' => 'infected']);

        $this->assertNull($doc->fresh()->fileSummary()['url']);
        $this->actingAs($owner)->get($doc->fresh()->downloadUrl())->assertStatus(423);
    }

    public function test_business_users_cannot_reach_staff_areas(): void
    {
        $user = $this->businessUser();
        foreach (['/fa/admin', '/fa/review', '/fa/admin/users', '/fa/admin/audit'] as $url) {
            $this->actingAs($user)->get($url)->assertForbidden();
        }
    }

    public function test_content_manager_permissions_are_scoped(): void
    {
        $cm = $this->staff(Role::ContentManager);
        $this->actingAs($cm)->get('/fa/admin/knowledge')->assertOk();
        $this->actingAs($cm)->get('/fa/admin/users')->assertForbidden();
        $this->actingAs($cm)->get('/fa/review')->assertForbidden();
    }

    public function test_admins_must_enable_two_factor_when_required(): void
    {
        config(['platform.require_admin_2fa' => true]);
        $admin = $this->staff(Role::Admin);

        $this->actingAs($admin)->get('/fa/admin')->assertRedirect(route('settings.security', ['locale' => 'fa']));
    }

    public function test_only_super_admin_can_grant_admin_roles(): void
    {
        $admin = $this->staff(Role::Admin);
        $target = $this->businessUser();

        $this->actingAs($admin)->put("/fa/admin/users/{$target->id}", ['roles' => ['business', 'admin'], 'status' => 'active'])->assertForbidden();
        $this->actingAs($admin)->put("/fa/admin/users/{$target->id}", ['roles' => ['business', 'content_manager'], 'status' => 'active'])->assertSessionHasNoErrors();
        $this->assertTrue($target->fresh()->hasRole('content_manager'));
        $this->assertTrue(AuditLog::where('action', 'user.roles_updated')->exists());
    }

    public function test_email_otp_login(): void
    {
        Notification::fake();
        $user = $this->businessUser();

        $this->post('/fa/login/code', ['email' => $user->email])->assertRedirect();
        $code = null;
        Notification::assertSentTo($user, LoginCodeNotification::class, function ($n) use (&$code) {
            $code = (fn () => $this->code)->call($n);

            return true;
        });

        $this->post('/fa/login/code/verify', ['email' => $user->email, 'code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/fa/login/code/verify', ['email' => $user->email, 'code' => $code])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull(LoginCode::first()->used_at);
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/fa')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }
}
