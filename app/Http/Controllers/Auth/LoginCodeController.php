<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginCode;
use App\Models\User;
use App\Notifications\LoginCodeNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Passwordless sign-in with a one-time email code (OTP). Respects two-factor authentication. */
class LoginCodeController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Auth/LoginCode', ['email' => $request->session()->get('otp_email')]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        $user = User::where('email', strtolower($data['email']))->where('status', 'active')->first();

        if ($user) {
            LoginCode::where('user_id', $user->id)->whereNull('used_at')->update(['used_at' => now()]);
            $code = (string) random_int(100000, 999999);
            LoginCode::create([
                'user_id' => $user->id,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes(config('platform.otp_ttl_minutes')),
                'ip_address' => $request->ip(),
            ]);
            $user->notify(new LoginCodeNotification($code));
        }

        $request->session()->put('otp_email', strtolower($data['email']));

        return redirect()->route('login.code')->with('status', __('auth.otp_sent'));
    }

    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'code' => ['required', 'digits:6']]);
        $user = User::where('email', strtolower($data['email']))->first();
        $record = $user ? LoginCode::where('user_id', $user->id)->latest('id')->first() : null;

        if (! $record || ! $record->isUsable() || ! Hash::check($data['code'], $record->code_hash)) {
            $record?->increment('attempts');
            throw ValidationException::withMessages(['code' => __('auth.otp_invalid')]);
        }
        $record->update(['used_at' => now()]);

        if ($user->hasTwoFactorEnabled()) {
            $request->session()->put(['login.id' => $user->getKey(), 'login.remember' => false]);

            return redirect()->route('two-factor.login');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('otp_email');

        return redirect()->intended(route($user->homeRouteName()));
    }
}
