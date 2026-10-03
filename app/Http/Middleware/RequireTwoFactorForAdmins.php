<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admins must enable two-factor authentication before using the admin panel. */
class RequireTwoFactorForAdmins
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (config('platform.require_admin_2fa') && $user?->isStaff() && ! $user->hasTwoFactorEnabled()) {
            return redirect()->route('settings.security')->with('warning', __('auth.admin_2fa_required'));
        }

        return $next($request);
    }
}
