<?php

use App\Http\Middleware\EnsureBusinessOnboarded;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireTwoFactorForAdmins;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            SecurityHeaders::class,
        ]);
        $middleware->alias([
            'locale' => SetLocale::class,
            'staff' => EnsureStaff::class,
            'admin.2fa' => RequireTwoFactorForAdmins::class,
            'onboarded' => EnsureBusinessOnboarded::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
        // SetLocale must run before route-model binding so localized redirects work.
        $middleware->prependToPriorityList(SubstituteBindings::class, SetLocale::class);
        $middleware->redirectGuestsTo(fn (Request $request) => route('login', ['locale' => app()->getLocale()]));
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()->homeRouteName(), ['locale' => app()->getLocale()]));
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (! app()->environment(['local', 'testing']) && in_array($response->getStatusCode(), [403, 404, 500, 503], true) && ! $request->expectsJson()) {
                return Inertia::render('Error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)->setStatusCode($response->getStatusCode());
            }
            if ($response->getStatusCode() === 419) {
                return back()->with('error', __('app.page_expired'));
            }

            return $response;
        });
    })->create();
