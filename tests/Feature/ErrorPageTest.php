<?php

namespace Tests\Feature;

use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    /** In production every error page is the app's own page, with the layout's shared props. */
    public function test_not_found_pages_render_with_shared_props_in_production(): void
    {
        $this->app['env'] = 'production';

        foreach (['/fa/not-a-real-page' => 'fa', '/en/knowledge/missing-article' => 'en', '/zz/unknown' => 'fa'] as $url => $locale) {
            $this->get($url, ['Accept-Language' => 'fa-IR,fa;q=0.9'])->assertNotFound()->assertInertia(fn ($page) => $page->component('Error')
                ->where('status', 404)
                ->where('app.locale', $locale)
                ->has('ziggy.routes.home'));
        }
    }
}
