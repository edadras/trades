<?php

namespace App\Http\Middleware;

use App\Domain\AI\Models\AiHumanReview;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Identity\Enums\Permission;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'app' => [
                'name' => config('app.name'),
                'locale' => $locale,
                'locales' => config('platform.locales'),
                'dir' => in_array($locale, config('platform.rtl_locales'), true) ? 'rtl' : 'ltr',
                'url' => config('app.url'),
                'reverb' => config('broadcasting.default') === 'reverb',
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'timezone' => $user->timezone ?: config('platform.default_timezone', 'Asia/Tehran'),
                    'avatar' => $user->avatar_path,
                    'roles' => $user->getRoleNames(),
                    'permissions' => $user->getAllPermissions()->pluck('name'),
                    'is_staff' => $user->isStaff(),
                    'is_admin' => $user->isAdmin(),
                    'has_business' => (bool) ($business = $user->currentBusiness()),
                    'business' => $business ? ['id' => $business->id, 'name' => $business->trade_name, 'role' => $business->roleOf($user)] : null,
                    'businesses' => $user->businesses()->get(['businesses.id', 'trade_name'])->map(fn ($b) => ['id' => $b->id, 'name' => $b->trade_name])->values(),
                    'can_create_case' => $user->can('create', SupportCase::class),
                    'expert_status' => $user->expertProfile?->verification_status?->value,
                    'two_factor' => $user->hasTwoFactorEnabled(),
                    'email_verified' => (bool) $user->email_verified_at,
                    'home' => $user->homeRouteName(),
                ] : null,
                'unread_notifications' => fn () => $user?->unreadNotifications()->count() ?? 0,
                'review_queue' => fn () => $user?->can(Permission::CasesReview->value) ? AiHumanReview::pending()->count() : 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'status' => fn () => $request->session()->get('status'),
            ],
            'options' => fn () => $this->options(),
            'ziggy' => fn () => [...(new Ziggy)->toArray(), 'location' => $request->url()],
        ];
    }

    /** Localised option lists used by forms (industries, countries, ...). */
    private function options(): array
    {
        $pairs = fn (string $group, array $keys) => collect($keys)->map(fn ($k) => ['value' => $k, 'label' => __("options.{$group}.{$k}")])->values()->all();

        return [
            'industries' => $pairs('industries', config('platform.industries')),
            'countries' => $pairs('countries', config('platform.countries')),
            'provinces' => $pairs('provinces', config('platform.provinces.IR')),
            'needs' => $pairs('needs', config('platform.main_needs')),
            'sizes' => $pairs('sizes', config('platform.company_sizes')),
            'employee_ranges' => collect(config('platform.employee_ranges'))->map(fn ($r) => ['value' => $r, 'label' => $r])->all(),
            'languages' => $pairs('languages', config('platform.locales')),
        ];
    }
}
