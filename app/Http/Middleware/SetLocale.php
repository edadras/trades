<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the {locale} route prefix (/fa/..., /en/...), applies it and removes it from the
 * route parameters so controllers never have to accept it.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');
        $supported = config('platform.locales');

        if (! in_array($locale, $supported, true)) {
            $preferred = $request->user()?->locale ?? $request->getPreferredLanguage($supported) ?? config('app.locale');
            $path = preg_replace('#^/?[^/]*#', '', $request->getPathInfo(), 1);

            return redirect('/'.$preferred.$path.($request->getQueryString() ? '?'.$request->getQueryString() : ''));
        }

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);
        $request->route()->forgetParameter('locale');

        if ($request->user() && $request->user()->locale !== $locale && $request->isMethod('GET')) {
            $request->user()->forceFill(['locale' => $locale])->saveQuietly();
        }

        return $next($request);
    }
}
