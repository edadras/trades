<?php

namespace Tests\Feature;

use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\CaseOutcome;
use App\Domain\Compliance\Models\Complaint;
use App\Domain\Identity\Enums\Role;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OutcomeConfirmationTest extends TestCase
{
    public function test_expert_outcome_waits_for_business_confirmation_and_dispute_opens_a_complaint(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();
        $case = $this->engage($this->submitCase($business), $expert);
        $ops = $this->staff(Role::OperationsManager);

        Notification::fake();
        $this->actingAs($expert->user)->post("/fa/cases/{$case->number}/outcome", ['outcome' => 'resolved', 'reason' => 'Compressor leaks fixed'])->assertSessionHasNoErrors();
        $this->assertSame('pending', $case->outcome()->first()->confirmation_status);
        $this->assertSame(CaseStatus::InProgress, $case->fresh()->status, 'an unconfirmed outcome does not resolve the case');
        Notification::assertSentTo($business, PlatformNotification::class, fn ($n) => $n->event === 'outcome_confirmation_requested');

        // Closing is refused until the business confirms.
        $this->actingAs($expert->user)->post("/fa/cases/{$case->number}/close")->assertSessionHasErrors('outcome');
        $this->actingAs($expert->user)->post("/fa/cases/{$case->number}/outcome/confirm", ['confirm' => true])->assertForbidden();

        $this->actingAs($business)->post("/fa/cases/{$case->number}/outcome/confirm", ['confirm' => false])->assertSessionHasErrors('reason');
        $this->actingAs($business)->post("/fa/cases/{$case->number}/outcome/confirm", ['confirm' => false, 'reason' => 'The bill is still high'])->assertSessionHasNoErrors();
        $this->assertSame('disputed', $case->outcome()->first()->confirmation_status);
        $this->assertDatabaseHas('complaints', ['case_id' => $case->id, 'category' => 'outcome_dispute']);
        Notification::assertSentTo($ops, PlatformNotification::class, fn ($n) => $n->event === 'complaint_received');

        // A new outcome supersedes the disputed one; the business confirms it and the case resolves.
        $this->actingAs($expert->user)->post("/fa/cases/{$case->number}/outcome", ['outcome' => 'partially_resolved', 'reason' => 'Second compressor replaced'])->assertSessionHasNoErrors();
        $this->actingAs($business)->post("/fa/cases/{$case->number}/outcome/confirm", ['confirm' => true])->assertSessionHasNoErrors();
        $this->assertSame(CaseStatus::Resolved, $case->fresh()->status);
        $this->assertSame(2, CaseOutcome::where('case_id', $case->id)->count());
        $this->assertSame(1, CaseOutcome::where('case_id', $case->id)->whereNotNull('superseded_at')->count());

        $this->actingAs($business)->post("/fa/cases/{$case->number}/close")->assertSessionHasNoErrors();
        $this->assertSame(CaseStatus::Closed, $case->fresh()->status);
    }

    public function test_closed_case_can_be_reopened_with_history_kept(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();
        $case = $this->engage($this->submitCase($business), $expert);
        $this->actingAs($business)->post("/fa/cases/{$case->number}/outcome", ['outcome' => 'resolved', 'reason' => 'Consumption back to normal'])->assertSessionHasNoErrors();
        $this->actingAs($business)->post("/fa/cases/{$case->number}/close")->assertSessionHasNoErrors();

        $this->actingAs($business)->post("/fa/cases/{$case->number}/reopen", ['reason' => 'Costs rose again this month'])->assertSessionHasNoErrors();

        $case->refresh();
        $this->assertSame(CaseStatus::InProgress, $case->status);
        $this->assertNull($case->outcome()->first(), 'the previous outcome is history, not current');
        $this->assertSame(1, CaseOutcome::where('case_id', $case->id)->whereNotNull('superseded_at')->count());
        $this->assertTrue($case->hasActiveExpert($expert->user), 'the expert rejoins the reopened case');
        $this->assertTrue($case->events()->where('type', 'case_reopened')->exists());
    }

    public function test_business_cannot_reopen_after_the_window(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();
        $case = $this->engage($this->submitCase($business), $expert);
        $this->actingAs($business)->post("/fa/cases/{$case->number}/outcome", ['outcome' => 'resolved', 'reason' => 'Consumption back to normal']);
        $this->actingAs($business)->post("/fa/cases/{$case->number}/close");
        $case->fresh()->forceFill(['closed_at' => now()->subDays(61)])->save();

        $this->actingAs($business)->post("/fa/cases/{$case->number}/reopen", ['reason' => 'Costs rose again'])->assertForbidden();
    }

    public function test_pending_outcomes_are_auto_confirmed_after_the_confirmation_period(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();
        $case = $this->engage($this->submitCase($business), $expert);
        $this->actingAs($expert->user)->post("/fa/cases/{$case->number}/outcome", ['outcome' => 'effective_action_started', 'reason' => 'Repairs scheduled']);
        $outcome = $case->outcome()->first();

        $this->artisan('outcomes:auto-confirm')->assertSuccessful();
        $this->assertSame('pending', $outcome->fresh()->confirmation_status, 'not before the period ends');

        $outcome->forceFill(['created_at' => now()->subDays(config('platform.outcome_confirmation_days') + 1)])->save();
        $this->artisan('outcomes:auto-confirm')->assertSuccessful();
        $this->assertSame('confirmed', $outcome->fresh()->confirmation_status);
        $this->assertNull($outcome->fresh()->confirmed_by);
        $this->assertSame(CaseStatus::Resolved, $case->fresh()->status);
    }

    public function test_low_rating_requires_a_dissatisfaction_reason(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();
        $case = $this->engage($this->submitCase($business), $expert);
        $this->actingAs($business)->post("/fa/cases/{$case->number}/outcome", ['outcome' => 'unresolved', 'reason' => 'Nothing changed']);

        $this->actingAs($business)->post("/fa/cases/{$case->number}/satisfaction", ['rating' => 2])->assertSessionHasErrors('dissatisfaction_reason');
        $this->actingAs($business)->post("/fa/cases/{$case->number}/satisfaction", ['rating' => 2, 'dissatisfaction_reason' => 'slow_response'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('satisfaction_surveys', ['case_id' => $case->id, 'dissatisfaction_reason' => 'slow_response']);
        $this->assertSame(0, Complaint::count());
    }
}
