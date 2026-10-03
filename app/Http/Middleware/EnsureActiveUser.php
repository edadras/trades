<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Ends the session of a user who was suspended while signed in. */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->status !== null && $user->status !== 'active') {
            abort_unless($request->hasSession(), 403, __('auth.suspended'));
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login', ['locale' => app()->getLocale()])->with('error', __('auth.suspended'));
        }

        return $next($request);
    }
}
