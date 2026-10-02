<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\Seo;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function about(): Response
    {
        return Inertia::render('Public/About', ['seo' => Seo::page('about')]);
    }

    public function howItWorks(): Response
    {
        return Inertia::render('Public/HowItWorks', ['seo' => Seo::page('how')]);
    }

    public function privacy(): Response
    {
        return Inertia::render('Public/Legal', ['page' => 'privacy', 'seo' => Seo::page('privacy')]);
    }

    public function terms(): Response
    {
        return Inertia::render('Public/Legal', ['page' => 'terms', 'seo' => Seo::page('terms')]);
    }
}
