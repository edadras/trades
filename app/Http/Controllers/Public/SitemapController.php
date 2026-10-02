<?php

namespace App\Http\Controllers\Public;

use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [];
        foreach (config('platform.locales') as $locale) {
            foreach (['home', 'about', 'how-it-works', 'knowledge.index', 'experts.directory'] as $name) {
                $urls[] = route($name, ['locale' => $locale]);
            }
            foreach (KnowledgeArticle::approved()->get(['slug', 'updated_at']) as $article) {
                $urls[] = route('knowledge.show', ['locale' => $locale, 'article' => $article->slug]);
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .collect($urls)->map(fn ($u) => '<url><loc>'.e($u).'</loc></url>')->implode('').'</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
