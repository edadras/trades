<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/** Sends each user to their own area (business, expert, review, admin) in their language. */
class LoginResponse implements LoginResponseContract, RegisterResponseContract, TwoFactorLoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 200);
        }
        $user = $request->user();
        $locale = in_array(app()->getLocale(), config('platform.locales'), true) ? app()->getLocale() : ($user->locale ?: 'fa');

        return redirect()->intended(route($user->homeRouteName(), ['locale' => $locale]));
    }
}
