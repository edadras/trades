<?php

namespace App\Domain\AI\Safety;

use App\Domain\AI\Support\TextNormalizer;

/**
 * Flags cases that must not be handled by AI alone. Any flag sends the case to human review.
 */
class SafetyGuard
{
    /** @var array<string, array<int, string>> */
    private const RULES = [
        'legal_dispute' => ['شکایت', 'دادگاه', 'دعوی', 'وکیل', 'حکم', 'lawsuit', 'court', 'litigation', 'sued', 'attorney', 'قرارداد فسخ'],
        'insolvency' => ['ورشکست', 'ورشکستگی', 'چک برگشتی', 'توقیف', 'bankrupt', 'insolven', 'bounced cheque', 'seized', 'foreclos'],
        'safety_hazard' => ['انفجار', 'آتش سوزی', 'نشت گاز', 'مصدوم', 'حادثه', 'explosion', 'caught fire', 'gas leak', 'injur', 'fatal'],
        'sanctions_compliance' => ['تحریم', 'پولشویی', 'sanction', 'money laundering', 'aml', 'embargo'],
        'personal_data' => ['اطلاعات شخصی', 'نشت اطلاعات', 'هک شد', 'data breach', 'leak of customer', 'hacked', 'ransomware'],
        'labor_conflict' => ['اعتصاب', 'اخراج دسته جمعی', 'strike', 'mass layoff'],
        'health' => ['خودکشی', 'suicide', 'self-harm'],
    ];

    /** @return array<int, string> flag keys */
    public function inspect(string $text): array
    {
        $normalized = TextNormalizer::normalize($text);
        $flags = [];
        foreach (self::RULES as $flag => $needles) {
            foreach ($needles as $needle) {
                if (TextNormalizer::contains($normalized, $needle)) {
                    $flags[] = $flag;
                    break;
                }
            }
        }

        return $flags;
    }
}
