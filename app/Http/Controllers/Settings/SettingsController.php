<?php

namespace App\Http\Controllers\Settings;

use App\Domain\Compliance\Actions\ProcessDataRequest;
use App\Domain\Compliance\Models\DataRequest;
use App\Domain\Identity\Actions\RecordConsent;
use App\Domain\Identity\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Consent;
use App\Notifications\PlatformNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function profile(Request $request): Response
    {
        return Inertia::render('Settings/Profile', [
            'profile' => $request->user()->only(['name', 'email', 'locale', 'timezone']) + ['phone' => $request->user()->phone],
        ]);
    }

    public function security(Request $request): Response
    {
        $user = $request->user();
        $sessions = config('session.driver') === 'database'
            ? DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->get()->map(fn ($s) => [
                'id' => $s->id === $request->session()->getId() ? 'current' : substr(hash('sha256', $s->id), 0, 16),
                'ip' => $s->ip_address,
                'agent' => $this->describeAgent((string) $s->user_agent),
                'last_active' => date(DATE_ATOM, $s->last_activity),
                'current' => $s->id === $request->session()->getId(),
            ])
            : collect();

        return Inertia::render('Settings/Security', [
            'twoFactor' => [
                'enabled' => ! is_null($user->two_factor_secret),
                'confirmed' => $user->hasTwoFactorEnabled(),
                'required' => $user->isAdmin() && config('platform.require_admin_2fa'),
            ],
            'sessions' => $sessions,
        ]);
    }

    /** Sign out every other device after confirming the password. */
    public function logoutOtherSessions(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);
        Auth::logoutOtherDevices($request->input('password'));
        if (config('session.driver') === 'database') {
            DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
        }

        return back()->with('success', __('app.saved'));
    }

    public function notifications(Request $request): Response
    {
        return Inertia::render('Settings/Notifications', [
            'events' => PlatformNotification::EVENTS,
            'preferences' => $request->user()->notification_preferences ?? [],
            'hasPhone' => (bool) $request->user()->phone,
        ]);
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate(['preferences' => ['array'], 'preferences.*.mail' => ['boolean'], 'preferences.*.sms' => ['boolean'], 'preferences.*.whatsapp' => ['boolean']]);
        $prefs = collect($data['preferences'] ?? [])->only(PlatformNotification::EVENTS)->all();
        $request->user()->update(['notification_preferences' => $prefs]);

        return back()->with('success', __('app.saved'));
    }

    public function privacy(Request $request): Response
    {
        $latest = Consent::where('user_id', $request->user()->id)->orderByDesc('id')->get()->unique('type')
            ->mapWithKeys(fn ($c) => [$c->type => ['granted' => $c->granted, 'version' => $c->version, 'at' => $c->created_at->toIso8601String()]]);

        return Inertia::render('Settings/Privacy', [
            'consents' => $latest,
            'dataRequests' => DataRequest::where('user_id', $request->user()->id)->latest()->get()->map(fn ($d) => [
                'id' => $d->id, 'type' => $d->type, 'status' => $d->status, 'resolution' => $d->resolution, 'created_at' => $d->created_at->toIso8601String(),
                'download_url' => $d->type === 'export' && $d->status === 'completed' && $d->file_path ? URL::temporarySignedRoute('settings.data.download', now()->addMinutes(10), ['locale' => app()->getLocale(), 'dataRequest' => $d->id]) : null,
            ]),
            'history' => Consent::where('user_id', $request->user()->id)->latest('id')->limit(30)->get(['type', 'granted', 'version', 'created_at']),
        ]);
    }

    public function updatePrivacy(Request $request, RecordConsent $consent): RedirectResponse
    {
        $data = $request->validate(['type' => ['required', 'in:marketing,ai_processing'], 'granted' => ['required', 'boolean']]);
        $consent->handle($request->user(), $data['type'], $data['granted']);

        return back()->with('success', __('app.saved'));
    }

    public function requestData(Request $request, ProcessDataRequest $action): RedirectResponse
    {
        $data = $request->validate(['type' => ['required', 'in:export,delete'], 'reason' => ['nullable', 'string', 'max:1000'], 'password' => ['required_if:type,delete', 'nullable', 'current_password']]);
        $pending = DataRequest::where('user_id', $request->user()->id)->where('type', $data['type'])->where('status', 'pending')->exists();
        abort_if($pending, 409);
        $action->open($request->user(), $data['type'], $data['reason'] ?? null);

        return back()->with('success', __('privacy.request_received'));
    }

    public function downloadData(Request $request, DataRequest $dataRequest, AuditLogger $audit)
    {
        abort_unless($dataRequest->user_id === $request->user()->id && $dataRequest->file_path, 403);
        $audit->log('privacy.export.downloaded', $request->user());

        return Storage::disk(config('platform.uploads.disk'))->download($dataRequest->file_path, 'hamyar-data-export.json', ['Cache-Control' => 'private, no-store']);
    }

    private function describeAgent(string $agent): string
    {
        $browser = collect(['Edg' => 'Edge', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'])->first(fn ($name, $needle) => str_contains($agent, $needle)) ?? 'Browser';
        $os = collect(['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'Mac OS' => 'macOS', 'Linux' => 'Linux'])->first(fn ($name, $needle) => str_contains($agent, $needle)) ?? '';

        return trim("{$browser} {$os}");
    }
}
