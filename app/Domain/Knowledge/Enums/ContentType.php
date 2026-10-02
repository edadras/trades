<?php

namespace App\Domain\Knowledge\Enums;

enum ContentType: string
{
    case Article = 'article';
    case Guide = 'guide';
    case Checklist = 'checklist';
    case Video = 'video';
    case Pdf = 'pdf';
    case Faq = 'faq';
    case Template = 'template';
    case Regulation = 'regulation';
    case CaseStudy = 'case_study';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
