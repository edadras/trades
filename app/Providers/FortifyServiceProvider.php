<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Domain\Business\Models\BusinessInvitation;
use App\Domain\Business\Models\Partner;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(RegisterResponseContract::class, LoginResponse::class);
        $this->app->singleton(TwoFactorLoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::loginView(fn () => Inertia::render('Auth/Login', ['status' => session('status')]));
        Fortify::registerView(function (Request $request) {
            $invitation = $request->query('invitation') ? BusinessInvitation::findByToken($request->query('invitation')) : null;
            $ref = $request->query('ref', $request->session()->get('referral'));
            $partner = $ref ? Partner::where('referral_code', strtoupper($ref))->where('is_active', true)->first() : null;

            return Inertia::render('Auth/Register', [
                'type' => $request->query('type', 'business'),
                'referral' => $partner ? ['code' => $partner->referral_code, 'name' => $partner->translate('name')] : null,
                'invitation' => $invitation?->isPending() ? ['token' => $request->query('invitation'), 'email' => $invitation->email, 'business' => $invitation->business->trade_name] : null,
            ]);
        });
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('Auth/ForgotPassword', ['status' => session('status')]));
        Fortify::resetPasswordView(fn (Request $request) => Inertia::render('Auth/ResetPassword', ['token' => $request->route('token'), 'email' => $request->query('email')]));
        Fortify::verifyEmailView(fn () => Inertia::render('Auth/VerifyEmail', ['status' => session('status')]));
        Fortify::twoFactorChallengeView(fn () => Inertia::render('Auth/TwoFactorChallenge'));
        Fortify::confirmPasswordView(fn () => Inertia::render('Auth/ConfirmPassword'));

        RateLimiter::for('login', function (Request $request) {
            $key = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return [Limit::perMinute(5)->by($key), Limit::perMinute(30)->by($request->ip())];
        });
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by($request->session()->get('login.id')));
    }
}
