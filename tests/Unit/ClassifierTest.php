<?php

namespace Tests\Unit;

use App\Domain\AI\Classification\CaseClassifier;
use App\Domain\AI\Safety\PiiRedactor;
use App\Domain\AI\Safety\SafetyGuard;
use App\Domain\AI\Support\TextNormalizer;
use App\Domain\Cases\Enums\Urgency;
use Tests\TestCase;

class ClassifierTest extends TestCase
{
    public function test_classifies_persian_energy_problem(): void
    {
        $result = app(CaseClassifier::class)->classify('هزینه انرژی کارخانه ما طی سه ماه گذشته حدود ۳۰٪ افزایش یافته؛ قبض برق و مصرف گاز بالا رفته است.');

        $this->assertSame('energy', $result->category->slug);
        $this->assertSame('energy-high-consumption', $result->subcategory?->slug);
        $this->assertGreaterThanOrEqual(0.75, $result->confidence);
    }

    public function test_classifies_english_cash_flow_problem_with_high_urgency(): void
    {
        $result = app(CaseClassifier::class)->classify('Our cash flow is too low to cover payroll; we are losing money on receivables.');

        $this->assertSame('finance', $result->category->slug);
        $this->assertSame(Urgency::High, $result->urgency);
    }

    public function test_vague_text_has_low_confidence(): void
    {
        $result = app(CaseClassifier::class)->classify('اوضاع خوب نیست.');

        $this->assertLessThan(config('ai.confidence_threshold'), $result->confidence);
    }

    public function test_safety_guard_flags_legal_dispute(): void
    {
        $this->assertContains('legal_dispute', app(SafetyGuard::class)->inspect('مشتری تهدید به شکایت در دادگاه کرده است'));
        $this->assertSame([], app(SafetyGuard::class)->inspect('we want to improve our website'));
    }

    public function test_pii_is_redacted_before_leaving_the_platform(): void
    {
        $out = app(PiiRedactor::class)->redact('Call 09121234567 or mail ceo@example.com');

        $this->assertStringNotContainsString('09121234567', $out);
        $this->assertStringNotContainsString('ceo@example.com', $out);
    }

    public function test_normalizer_unifies_arabic_letters_and_digits(): void
    {
        $this->assertSame('کیفیت 30%', TextNormalizer::normalize('كيفيت ۳۰٪'));
    }
}
