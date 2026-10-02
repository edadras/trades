<?php

namespace Tests\Feature;

use App\Domain\Knowledge\Enums\ContentStatus;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    public function test_root_redirects_to_a_locale(): void
    {
        $this->get('/', ['Accept-Language' => 'en-US,en;q=0.9'])->assertRedirect('/en');
    }

    public function test_home_renders_in_both_languages_with_seo(): void
    {
        foreach (['fa', 'en'] as $locale) {
            $this->get("/{$locale}")->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Public/Home')
                ->where('app.locale', $locale)
                ->where('app.dir', $locale === 'fa' ? 'rtl' : 'ltr')
                ->where('seo.alternates.en', url('/en'))
                ->where('seo.alternates.fa', url('/fa'))
                ->has('categories'));
        }
    }

    public function test_unsupported_locale_is_not_found(): void
    {
        $this->get('/de/about')->assertNotFound();
    }

    public function test_only_approved_knowledge_is_public(): void
    {
        $article = KnowledgeArticle::first();
        $this->get("/fa/knowledge/{$article->slug}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Public/Knowledge/Show'));

        $article->update(['verification_status' => ContentStatus::Draft]);
        $this->get("/fa/knowledge/{$article->slug}")->assertNotFound();
    }

    public function test_knowledge_index_filters_by_category(): void
    {
        $this->get('/en/knowledge?category=energy')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/Knowledge/Index')
            ->where('articles.data', fn ($items) => collect($items)->every(fn ($a) => $a['category']['slug'] === 'energy') && count($items) > 0));
    }

    public function test_sitemap_lists_both_locales(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertSee(url('/fa/knowledge'), false)->assertSee(url('/en/knowledge'), false);
    }
}
