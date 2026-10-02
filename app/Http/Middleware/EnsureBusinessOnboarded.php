<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessOnboarded
{
    public function handle(Request $request, Closure $next): Response
    {
        $business = $request->user()?->currentBusiness();
        if (! $business || ! $business->isOnboarded()) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
