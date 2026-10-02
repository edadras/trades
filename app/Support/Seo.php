<?php

namespace App\Support;

use App\Domain\Knowledge\Models\KnowledgeArticle;
use Illuminate\Support\Str;

/** Title, description, canonical, hreflang, OG and Schema.org data for public pages, per locale. */
class Seo
{
    private const PAGES = [
        'home' => ['route' => 'home', 'fa' => ['همیار | پلتفرم هوشمند حمایت از کسب‌وکارها', 'مسئله کسب‌وکار خود را مطرح کنید؛ راهکار اولیه، آموزش و پشتیبان متخصص مناسب را پیدا کنید و همکاری را تا نتیجه پیگیری کنید.'], 'en' => ['Hamyar | Smart business support platform', 'Describe your business problem and get initial guidance, learning and the right expert — then follow the collaboration through to a result.']],
        'about' => ['route' => 'about', 'fa' => ['درباره همیار', 'همیار شبکه‌ای از کسب‌وکارها، هوش مصنوعی و متخصصان است که برای حل مسائل واقعی کسب‌وکارها کنار هم کار می‌کنند.'], 'en' => ['About Hamyar', 'Hamyar connects businesses, AI and experts to solve real business problems together.']],
        'how' => ['route' => 'how-it-works', 'fa' => ['همیار چطور کار می‌کند؟', 'از ثبت مسئله تا تحلیل هوشمند، بررسی کارشناسی، انتخاب پشتیبان، همکاری و ثبت نتیجه.'], 'en' => ['How Hamyar works', 'From submitting a problem to AI analysis, expert review, choosing a supporter, collaboration and a recorded outcome.']],
        'knowledge' => ['route' => 'knowledge.index', 'fa' => ['مرکز آموزش همیار', 'راهنماها، چک‌لیست‌ها و تجربه‌های تأییدشده برای مدیریت مالی، تولید، صادرات، انرژی و بیشتر.'], 'en' => ['Hamyar learning centre', 'Approved guides, checklists and case studies on finance, production, export, energy and more.']],
        'experts' => ['route' => 'experts.directory', 'fa' => ['شبکه متخصصان همیار', 'پشتیبانان تأییدشده داخل و خارج از کشور در حوزه‌های تخصصی کسب‌وکار.'], 'en' => ['Hamyar expert network', 'Verified supporters in Iran and abroad across key business domains.']],
        'privacy' => ['route' => 'privacy', 'fa' => ['حریم خصوصی', 'نحوه نگهداری و حفاظت از اطلاعات کسب‌وکارها و متخصصان.'], 'en' => ['Privacy policy', 'How business and expert information is stored and protected.']],
        'terms' => ['route' => 'terms', 'fa' => ['شرایط استفاده', 'قواعد استفاده از پلتفرم همیار.'], 'en' => ['Terms of use', 'Rules for using the Hamyar platform.']],
    ];

    public static function page(string $key): array
    {
        $page = self::PAGES[$key];
        [$title, $description] = $page[app()->getLocale()] ?? $page['en'];

        return self::build($title, $description, fn ($locale) => route($page['route'], ['locale' => $locale]), $key === 'home' ? self::organization() : null);
    }

    public static function forArticle(KnowledgeArticle $article): array
    {
        $t = $article->translation();
        $title = ($t?->seo_title ?: $t?->title).' | '.config('app.name');
        $description = $t?->seo_description ?: Str::limit(strip_tags((string) ($t?->summary ?: $t?->body)), 160);

        return self::build($title, $description, fn ($locale) => route('knowledge.show', ['locale' => $locale, 'article' => $article->slug]), [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $t?->title,
            'description' => $description,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'inLanguage' => $t?->locale,
            'publisher' => self::organization(),
        ], $article->cover_image, 'article', $article->translations->pluck('locale')->all());
    }

    private static function organization(): array
    {
        return ['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => config('app.name'), 'url' => config('app.url')];
    }

    private static function build(string $title, string $description, callable $url, ?array $schema = null, ?string $image = null, string $type = 'website', ?array $locales = null): array
    {
        $locales ??= config('platform.locales');

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $url(app()->getLocale()),
            'alternates' => collect($locales)->mapWithKeys(fn ($l) => [$l => $url($l)])->all() + ['x-default' => $url(config('app.locale'))],
            'og' => ['type' => $type, 'image' => $image ? url($image) : url('/images/og-'.app()->getLocale().'.png'), 'locale' => app()->getLocale() === 'fa' ? 'fa_IR' : 'en_US'],
            'schema' => $schema,
        ];
    }
}
