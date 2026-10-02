<?php

namespace App\Providers;

use App\Domain\AI\AIManager;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Identity\AuditLogger;
use App\Events\CaseStatusChanged;
use App\Listeners\NotifyCaseStatusChanged;
use App\Policies\SupportCasePolicy;
use App\Services\Files\ClamAvScanner;
use App\Services\Files\FileScanner;
use App\Services\Files\NullScanner;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AIManager::class);
        $this->app->bind(FileScanner::class, fn () => config('platform.scanner.driver') === 'clamav' ? new ClamAvScanner : new NullScanner);
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
        URL::defaults(['locale' => config('app.locale')]);
        Route::pattern('locale', implode('|', config('platform.locales')));

        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'business' => \App\Domain\Business\Models\Business::class,
            'case' => SupportCase::class,
            'expert_profile' => \App\Domain\Experts\Models\ExpertProfile::class,
            'knowledge_article' => \App\Domain\Knowledge\Models\KnowledgeArticle::class,
            'case_document' => \App\Domain\Cases\Models\CaseDocument::class,
            'business_document' => \App\Domain\Business\Models\BusinessDocument::class,
            'expert_document' => \App\Domain\Experts\Models\ExpertDocument::class,
            'message_attachment' => \App\Domain\Messaging\Models\MessageAttachment::class,
            'kpi' => \App\Domain\Analytics\Models\Kpi::class,
        ]);

        Gate::policy(SupportCase::class, SupportCasePolicy::class);

        Event::listen(CaseStatusChanged::class, NotifyCaseStatusChanged::class);
        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            app(AuditLogger::class)->log('auth.login', $event->user, [], $event->user->id);
        });
        Event::listen(Logout::class, fn (Logout $event) => $event->user && app(AuditLogger::class)->log('auth.logout', $event->user, [], $event->user->id));
        Event::listen(Failed::class, fn (Failed $event) => app(AuditLogger::class)->log('auth.failed', null, ['email' => $event->credentials['email'] ?? null], null));

        RateLimiter::for('ai', fn (Request $request) => Limit::perMinute(20)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(30)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('messages', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('otp', fn (Request $request) => [Limit::perMinute(3)->by(strtolower((string) $request->input('email')).'|'.$request->ip()), Limit::perHour(20)->by($request->ip())]);
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));
    }
}
