<?php

namespace Tests\Feature;

use App\Domain\AI\Models\AiHumanReview;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Enums\VerificationState;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Identity\Enums\Role;
use App\Domain\Matching\Enums\MatchStatus;
use App\Models\AuditLog;
use Tests\TestCase;

class CaseLifecycleTest extends TestCase
{
    private function submit($user, string $text): SupportCase
    {
        $this->actingAs($user)->post('/fa/cases', ['description' => $text])->assertRedirect();
        $case = SupportCase::latest('id')->first();
        $this->assertSame(CaseStatus::Draft, $case->status);
        $this->assertNotNull($case->answers()->first(), 'the AI asks a follow-up question');

        $question = $case->answers()->whereNull('answer')->first();
        $this->actingAs($user)->post("/fa/cases/{$case->number}/intake", ['key' => $question->question_key, 'answer' => 'از سه ماه پیش، حدود ۳۰ درصد'])->assertRedirect();
        $this->actingAs($user)->post("/fa/cases/{$case->number}/submit")->assertRedirect(route('cases.show', ['locale' => 'fa', 'case' => $case->number]));

        return $case->fresh();
    }

    public function test_full_journey_from_problem_to_closed_case(): void
    {
        $business = $this->businessUser(['industry' => 'food']);
        $expert = $this->expert();

        $case = $this->submit($business, 'هزینه انرژی کارخانه ما طی سه ماه گذشته حدود ۳۰٪ افزایش یافته و نمی‌دانیم مشکل از تجهیزات است یا الگوی مصرف. قبض برق بالا رفته.');

        // AI analysed confidently → Ready → matched → expert proposed.
        $this->assertMatchesRegularExpression('/^CASE-\d{4}-\d{6}$/', $case->number);
        $this->assertSame('energy', $case->category->slug);
        $this->assertSame(VerificationState::AiSuggested, $case->classification_source);
        $this->assertSame(CaseStatus::ExpertProposed, $case->status);
        $this->assertNotNull($case->next_action);
        $this->assertGreaterThan(0, $case->recommendedContents()->count());
        $analysis = $case->latestAnalysis;
        $this->assertNotEmpty($analysis->guidance['suggested_actions']);
        foreach ($analysis->guidance['suggested_actions'] as $item) {
            $this->assertNotEmpty($item['sources'], 'guidance is grounded in approved knowledge');
        }

        $match = $case->matches()->first();
        $this->assertSame($expert->id, $match->expert_profile_id);
        $this->assertNotEmpty($match->reasons);

        // Before acceptance the expert cannot open the case.
        $this->actingAs($expert->user)->get("/fa/expert/cases/{$case->number}")->assertForbidden();

        // Business accepts → invitation (anonymised) → expert accepts.
        $this->actingAs($business)->post("/fa/cases/{$case->number}/matches/{$match->id}", ['accept' => true])->assertRedirect();
        $this->assertSame(MatchStatus::Invited, $match->fresh()->status);
        $this->actingAs($expert->user)->get('/fa/expert/invitations')->assertOk()
            ->assertDontSee($case->business->trade_name)->assertDontSee($case->business->contact_email);
        $this->actingAs($expert->user)->post("/fa/expert/invitations/{$match->id}", ['accept' => true])->assertRedirect();

        $case->refresh();
        $this->assertSame(CaseStatus::InProgress, $case->status);
        $this->assertNotNull($case->conversation);
        $this->assertTrue($case->conversation->hasMember($expert->user));
        $this->actingAs($expert->user)->get("/fa/expert/cases/{$case->number}")->assertOk();
        $this->assertTrue(AuditLog::where('action', 'case.viewed')->exists());

        // Collaboration: message, task, appointment.
        $this->actingAs($expert->user)->post("/fa/cases/{$case->number}/messages", ['body' => 'سلام @'.str_replace(' ', '_', $business->name)])->assertRedirect();
        $this->assertSame(1, $case->conversation->messages()->count());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $business->id]);
        $this->actingAs($expert->user)->post("/fa/cases/{$case->number}/tasks", ['title' => 'ارسال قبض', 'owner_role' => 'business', 'is_next_action' => true, 'due_at' => now()->addDays(3)->toDateTimeString()])->assertSessionHasNoErrors();
        $this->assertSame('ارسال قبض', $case->fresh()->next_action);
        $this->actingAs($expert->user)->post("/fa/cases/{$case->number}/appointments", ['title' => 'Kickoff', 'starts_at' => now()->addDay()->toDateTimeString(), 'ends_at' => now()->addDay()->addHour()->toDateTimeString()])->assertSessionHasNoErrors();

        // Closing without outcome is refused.
        $this->actingAs($business)->post("/fa/cases/{$case->number}/close")->assertSessionHasErrors('outcome');
        $this->actingAs($business)->post("/fa/cases/{$case->number}/outcome", ['outcome' => 'effective_action_started', 'reason' => 'Leak repairs started'])->assertSessionHasNoErrors();
        $this->assertSame(CaseStatus::Resolved, $case->fresh()->status);
        $this->actingAs($business)->post("/fa/cases/{$case->number}/close")->assertSessionHasNoErrors();
        $this->assertSame(CaseStatus::Closed, $case->fresh()->status);

        $this->actingAs($business)->post("/fa/cases/{$case->number}/satisfaction", ['rating' => 5, 'problem_solved' => false, 'would_recommend_expert' => true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('satisfaction_surveys', ['case_id' => $case->id, 'rating' => 5]);
        $this->assertGreaterThan(8, $case->events()->count(), 'every step is on the timeline');
    }

    public function test_low_confidence_case_goes_to_human_review_and_agreement_is_stored(): void
    {
        $business = $this->businessUser();
        $case = $this->submit($business, 'اوضاع کسب‌وکار این روزها خوب نیست.');

        $this->assertSame(CaseStatus::HumanReview, $case->status);
        $review = AiHumanReview::pending()->where('case_id', $case->id)->firstOrFail();
        $this->assertSame('low_confidence', $review->reason);

        $reviewer = $this->staff(Role::CaseExpert);
        $this->actingAs($reviewer)->get('/fa/review')->assertOk()->assertSee($case->number);

        $finance = CaseCategory::where('slug', 'finance')->first();
        $this->actingAs($reviewer)->post("/fa/review/cases/{$case->number}/decision", [
            'decision' => 'confirmed', 'category_id' => $finance->id, 'urgency' => 'high', 'notes' => 'Clarified by phone', 'run_matching' => false,
        ])->assertSessionHasNoErrors();

        $review->refresh();
        $this->assertSame('edited', $review->decision, 'a changed category is recorded as a correction');
        $this->assertFalse($review->category_agreed);
        $case->refresh();
        $this->assertSame(CaseStatus::Ready, $case->status);
        $this->assertSame(VerificationState::ExpertVerified, $case->classification_source);
        $this->assertSame($reviewer->id, $case->case_manager_id);
        $this->assertNotNull($case->first_reviewed_at);
        $this->assertDatabaseHas('case_notes', ['case_id' => $case->id, 'visibility' => 'internal']);
    }

    public function test_sensitive_case_always_needs_human_review(): void
    {
        $business = $this->businessUser();
        $case = $this->submit($business, 'مشتری بزرگ قرارداد را فسخ کرده و تهدید به شکایت در دادگاه کرده است. مهلت پاسخ هفته آینده است.');

        $this->assertSame(CaseStatus::HumanReview, $case->status);
        $this->assertTrue($case->is_sensitive);
        $this->assertContains('legal_dispute', $case->latestAnalysis->safety_flags);
    }

    public function test_business_rejection_triggers_new_matching(): void
    {
        $business = $this->businessUser();
        $this->expert();
        $second = $this->expert();
        $case = $this->submit($business, 'هزینه انرژی و مصرف برق کارخانه زیاد شده و قبض برق دو برابر شده است.');

        foreach ($case->matches()->get() as $m) {
            $this->actingAs($business)->post("/fa/cases/{$case->number}/matches/{$m->id}", ['accept' => false, 'reason' => 'no']);
        }
        $this->assertSame(0, $case->matches()->where('status', 'proposed')->count());
        $this->assertSame(CaseStatus::Matching, $case->fresh()->status);
    }

    public function test_other_businesses_cannot_see_a_case(): void
    {
        $owner = $this->businessUser();
        $other = $this->businessUser();
        $case = $this->submit($owner, 'مشکل نقدینگی برای پرداخت حقوق پرسنل داریم.');

        $this->actingAs($other)->get("/fa/cases/{$case->number}")->assertForbidden();
        $this->actingAs($other)->post("/fa/cases/{$case->number}/messages", ['body' => 'hi'])->assertForbidden();
    }
}
