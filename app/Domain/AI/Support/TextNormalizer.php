<?php

namespace App\Domain\AI\Support;

class TextNormalizer
{
    /** Normalises Persian/Arabic variants, digits and spacing so keyword matching works across scripts. */
    public static function normalize(string $text): string
    {
        $map = [
            'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه', 'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
            "\u{200C}" => ' ', "\u{200F}" => '', "\u{200E}" => '',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '٪' => '%',
        ];
        $text = strtr($text, $map);
        $text = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $text) ?? $text;
        $text = mb_strtolower($text);

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    private const STOPWORDS = [
        'از', 'به', 'با', 'در', 'را', 'که', 'این', 'ان', 'آن', 'و', 'یا', 'برای', 'تا', 'هم', 'ما', 'شما', 'من', 'او', 'است', 'هست', 'بود', 'شده',
        'شد', 'می', 'نمی', 'های', 'ها', 'یک', 'چه', 'چی', 'کنیم', 'کرد', 'کرده', 'کردیم', 'داریم', 'دارد', 'باید', 'خیلی', 'دیگر', 'روی', 'بر', 'کسب',
        'کار', 'وکار', 'اوضاع', 'خوب', 'نیست', 'دقیقا', 'کجا', 'شروع', 'روزها', 'هنوز', 'نکرده', 'ایم', 'چند', 'پیش',
        'the', 'and', 'or', 'of', 'to', 'in', 'on', 'for', 'is', 'are', 'was', 'were', 'our', 'we', 'it', 'its', 'a', 'an', 'by', 'with',
        'at', 'from', 'this', 'that', 'have', 'has', 'not', 'be', 'been', 'last', 'do', 'don', 'business',
    ];

    /** @return array<int, string> */
    public static function tokens(string $text): array
    {
        $parts = preg_split('/[^\p{L}\p{N}%]+/u', self::normalize($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = array_flip(array_map(fn ($w) => self::normalize($w), self::STOPWORDS));

        return array_values(array_filter($parts, fn ($t) => mb_strlen($t) > 1 && ! isset($stop[$t])));
    }

    public static function contains(string $normalizedHaystack, string $needle): bool
    {
        $needle = self::normalize($needle);

        return $needle !== '' && str_contains($normalizedHaystack, $needle);
    }

    public static function isPersian(string $text): bool
    {
        return (bool) preg_match('/[\x{0600}-\x{06FF}]/u', $text);
    }
}
