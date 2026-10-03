<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth\LoginCodeController;
use App\Http\Controllers\Business;
use App\Http\Controllers\Cases;
use App\Http\Controllers\Expert;
use App\Http\Controllers\FileDownloadController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Public;
use App\Http\Controllers\Review;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Support\SupportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::get('/', fn (Request $request) => redirect('/'.($request->user()?->locale ?? $request->getPreferredLanguage(config('platform.locales')) ?? config('app.locale'))));
Route::get('sitemap.xml', Public\SitemapController::class)->name('sitemap');

// Private files: short-lived signed URL + authenticated + authorised + audited.
Route::get('files/{type}/{id}', FileDownloadController::class)
    ->middleware(['auth', 'signed'])->whereNumber('id')->name('files.download');

Route::prefix('{locale}')->where(['locale' => 'fa|en'])->middleware('locale')->group(function () {
    // ── Public website (SEO) ─────────────────────────────────────────────
    Route::get('/', Public\HomeController::class)->name('home');
    Route::get('about', [Public\PageController::class, 'about'])->name('about');
    Route::get('how-it-works', [Public\PageController::class, 'howItWorks'])->name('how-it-works');
    Route::get('privacy', [Public\PageController::class, 'privacy'])->name('privacy');
    Route::get('terms', [Public\PageController::class, 'terms'])->name('terms');
    Route::get('knowledge', [Public\KnowledgeController::class, 'index'])->name('knowledge.index');
    Route::get('knowledge/{article:slug}', [Public\KnowledgeController::class, 'show'])->name('knowledge.show');
    Route::get('experts', Public\ExpertDirectoryController::class)->name('experts.directory');

    // ── Team invitation landing (guests are asked to sign in / register) ─
    Route::get('invitations/{token}', [Business\InvitationController::class, 'show'])->where('token', '[A-Za-z0-9]{48}')->name('invitations.show');

    // ── Passwordless sign-in (email OTP) ────────────────────────────────
    Route::middleware('guest')->group(function () {
        Route::get('login/code', [LoginCodeController::class, 'create'])->name('login.code');
        Route::post('login/code', [LoginCodeController::class, 'send'])->middleware('throttle:otp')->name('login.code.send');
        Route::post('login/code/verify', [LoginCodeController::class, 'verify'])->middleware('throttle:otp')->name('login.code.verify');
    });

    Route::middleware(['auth', 'verified'])->group(function () {
        // ── Notifications & settings (all users) ────────────────────────
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::get('notifications/{id}', [NotificationController::class, 'read'])->name('notifications.read');

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('profile', [SettingsController::class, 'profile'])->name('profile');
            Route::get('security', [SettingsController::class, 'security'])->name('security');
            Route::delete('sessions', [SettingsController::class, 'logoutOtherSessions'])->name('sessions.destroy');
            Route::get('notifications', [SettingsController::class, 'notifications'])->name('notifications');
            Route::put('notifications', [SettingsController::class, 'updateNotifications'])->name('notifications.update');
            Route::get('privacy', [SettingsController::class, 'privacy'])->name('privacy');
            Route::post('privacy', [SettingsController::class, 'updatePrivacy'])->name('privacy.update');
            Route::post('data', [SettingsController::class, 'requestData'])->middleware('throttle:6,60')->name('data.request');
            Route::get('data/{dataRequest}/download', [SettingsController::class, 'downloadData'])->middleware('signed')->name('data.download');
        });

        Route::post('invitations/{token}', [Business\InvitationController::class, 'accept'])->where('token', '[A-Za-z0-9]{48}')->name('invitations.accept');

        // ── Support & complaints (all users) ────────────────────────────
        Route::get('support', [SupportController::class, 'index'])->name('support.index');
        Route::post('support', [SupportController::class, 'store'])->middleware('throttle:10,60')->name('support.store');

        // ── Business panel ───────────────────────────────────────────────
        Route::middleware('role:business')->group(function () {
            Route::get('onboarding', [Business\OnboardingController::class, 'show'])->name('onboarding.show');
            Route::post('onboarding', [Business\OnboardingController::class, 'store'])->middleware('throttle:uploads')->name('onboarding.store');
            Route::post('business/switch', Business\SwitchBusinessController::class)->name('business.switch');

            Route::middleware('onboarded')->group(function () {
                Route::get('dashboard', Business\DashboardController::class)->name('dashboard');
                Route::get('learning', Business\LearningController::class)->name('learning');
                Route::get('business/profile', [Business\BusinessProfileController::class, 'edit'])->name('business.profile');
                Route::put('business/profile', [Business\BusinessProfileController::class, 'update'])->name('business.profile.update');
                Route::post('business/documents', [Business\BusinessProfileController::class, 'uploadDocument'])->middleware('throttle:uploads')->name('business.documents.store');
                Route::delete('business/documents/{document}', [Business\BusinessProfileController::class, 'destroyDocument'])->name('business.documents.destroy');
                Route::get('business/team', [Business\TeamController::class, 'index'])->name('business.team.index');
                Route::post('business/team', [Business\TeamController::class, 'invite'])->middleware('throttle:20,60')->name('business.team.invite');
                Route::put('business/team/{member}', [Business\TeamController::class, 'update'])->name('business.team.update');
                Route::delete('business/team/{member}', [Business\TeamController::class, 'destroy'])->name('business.team.destroy');
                Route::delete('business/invitations/{invitation}', [Business\TeamController::class, 'revoke'])->name('business.team.revoke');

                Route::get('cases', [Cases\CaseController::class, 'index'])->name('cases.index');
                Route::get('cases/new', [Cases\CaseController::class, 'create'])->name('cases.create');
                Route::post('cases', [Cases\CaseController::class, 'store'])->middleware('throttle:ai')->name('cases.store');
                Route::post('cases/{case}/intake', [Cases\IntakeController::class, 'answer'])->middleware('throttle:ai')->name('cases.intake.answer');
                Route::post('cases/{case}/submit', [Cases\IntakeController::class, 'submit'])->middleware('throttle:ai')->name('cases.submit');
                Route::post('cases/{case}/matches/{match}', [Cases\MatchController::class, 'decide'])->name('cases.matches.decide');
                Route::post('cases/{case}/satisfaction', [Cases\OutcomeController::class, 'satisfaction'])->name('cases.satisfaction');
                Route::post('cases/{case}/outcome/confirm', [Cases\CaseLifecycleController::class, 'confirmOutcome'])->name('cases.outcome.confirm');
                Route::get('cases/{case}', [Cases\CaseController::class, 'show'])->name('cases.show');
            });
        });

        // ── Shared case workspace actions (business, active expert, staff) ─
        Route::prefix('cases/{case}')->name('cases.')->group(function () {
            Route::get('voice', [Cases\CaseController::class, 'voice'])->name('voice');
            Route::post('documents', [Cases\WorkspaceController::class, 'uploadDocument'])->middleware('throttle:uploads')->name('documents.store');
            Route::post('documents/request', [Cases\WorkspaceController::class, 'requestDocument'])->name('documents.request');
            Route::post('tasks', [Cases\WorkspaceController::class, 'storeTask'])->name('tasks.store');
            Route::patch('tasks/{task}', [Cases\WorkspaceController::class, 'updateTask'])->name('tasks.update');
            Route::post('appointments', [Cases\WorkspaceController::class, 'storeAppointment'])->name('appointments.store');
            Route::post('notes', [Cases\WorkspaceController::class, 'storeNote'])->name('notes.store');
            Route::post('waiting', [Cases\WorkspaceController::class, 'setWaiting'])->name('waiting');
            Route::post('messages', [Cases\MessageController::class, 'store'])->middleware('throttle:messages')->name('messages.store');
            Route::get('messages', [Cases\MessageController::class, 'since'])->name('messages.since');
            Route::post('outcome', [Cases\OutcomeController::class, 'store'])->name('outcome.store');
            Route::post('close', [Cases\OutcomeController::class, 'close'])->name('close');
            Route::post('reopen', [Cases\CaseLifecycleController::class, 'reopen'])->name('reopen');
            Route::patch('details', [Cases\CaseLifecycleController::class, 'updateDetails'])->name('details.update');
            Route::post('collaboration', [Cases\CaseLifecycleController::class, 'requestCollaboration'])->name('collaboration.store');
            Route::post('leave', [Cases\CaseLifecycleController::class, 'leave'])->name('leave');
            Route::post('experts/{expert}/release', [Cases\CaseLifecycleController::class, 'releaseExpert'])->name('experts.release');
        });

        // ── Expert / supporter panel ─────────────────────────────────────
        Route::prefix('expert')->name('expert.')->group(function () {
            Route::get('profile', [Expert\ApplicationController::class, 'edit'])->name('profile.edit');
            Route::put('profile', [Expert\ApplicationController::class, 'update'])->name('profile.update');
            Route::post('profile/submit', [Expert\ApplicationController::class, 'submit'])->name('profile.submit');
            Route::post('profile/documents', [Expert\ApplicationController::class, 'uploadDocument'])->middleware('throttle:uploads')->name('profile.documents.store');

            Route::middleware('role:supporter')->group(function () {
                Route::get('dashboard', Expert\DashboardController::class)->name('dashboard');
                Route::get('invitations', [Expert\InvitationController::class, 'index'])->name('invitations.index');
                Route::post('invitations/{match}', [Expert\InvitationController::class, 'respond'])->name('invitations.respond');
                Route::get('cases', Expert\CaseListController::class)->name('cases.index');
                Route::get('cases/{case}', [Cases\CaseController::class, 'show'])->name('cases.show');
            });
        });

        // ── Staff case view: reviewers and every role allowed to see all cases (legal, programme, network) ─
        Route::get('review/cases/{case}', [Cases\CaseController::class, 'show'])
            ->middleware(['staff', 'admin.2fa', 'permission:cases.review|cases.view_all'])->name('review.cases.show');

        // ── Expert review panel (internal case experts) ─────────────────
        Route::prefix('review')->name('review.')->middleware(['staff', 'admin.2fa', 'permission:cases.review'])->group(function () {
            Route::get('/', [Review\ReviewQueueController::class, 'index'])->name('index');
            Route::get('cases', [Review\ReviewQueueController::class, 'cases'])->name('cases.index');
            Route::post('reviews/{review}/claim', [Review\CaseReviewController::class, 'claim'])->name('claim');
            Route::post('cases/{case}/decision', [Review\CaseReviewController::class, 'decide'])->name('cases.decide');
            Route::post('cases/{case}/reanalyze', [Review\CaseReviewController::class, 'reanalyze'])->middleware('throttle:ai')->name('cases.reanalyze');
            Route::post('cases/{case}/matching', [Review\CaseReviewController::class, 'runMatching'])->name('cases.matching');
            Route::get('cases/{case}/candidates', [Review\CaseReviewController::class, 'candidates'])->name('cases.candidates');
            Route::post('cases/{case}/propose', [Review\CaseReviewController::class, 'propose'])->name('cases.propose');
            Route::post('cases/{case}/manager', [Review\CaseReviewController::class, 'assignManager'])->name('cases.manager');
        });

        // ── Admin panel ─────────────────────────────────────────────────
        Route::prefix('admin')->name('admin.')->middleware(['staff', 'admin.2fa'])->group(function () {
            Route::get('/', Admin\DashboardController::class)->middleware('permission:analytics.view')->name('dashboard');

            Route::middleware('permission:kpis.manage')->group(function () {
                Route::get('kpis', [Admin\KpiController::class, 'index'])->name('kpis.index');
                Route::post('kpis', [Admin\KpiController::class, 'store'])->name('kpis.store');
                Route::put('kpis/{kpi}', [Admin\KpiController::class, 'update'])->name('kpis.update');
                Route::post('kpis/snapshot', [Admin\KpiController::class, 'snapshot'])->name('kpis.snapshot');
            });

            Route::middleware('permission:users.manage')->group(function () {
                Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
                Route::put('users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
            });

            Route::middleware('permission:businesses.view')->group(function () {
                Route::get('businesses', [Admin\BusinessController::class, 'index'])->name('businesses.index');
                Route::get('businesses/{business}', [Admin\BusinessController::class, 'show'])->name('businesses.show');
            });

            Route::middleware('permission:experts.view')->group(function () {
                Route::get('experts', [Admin\ExpertController::class, 'index'])->name('experts.index');
                Route::get('experts/{expert}', [Admin\ExpertController::class, 'show'])->name('experts.show');
                Route::post('experts/{expert}/verify', [Admin\ExpertController::class, 'verify'])->middleware('permission:experts.verify')->name('experts.verify');
            });

            Route::middleware('permission:categories.manage')->group(function () {
                Route::get('categories', [Admin\CategoryController::class, 'index'])->name('categories.index');
                Route::post('categories', [Admin\CategoryController::class, 'store'])->name('categories.store');
                Route::put('categories/{category}', [Admin\CategoryController::class, 'update'])->name('categories.update');
            });

            Route::middleware('permission:knowledge.manage|knowledge.approve')->group(function () {
                Route::get('knowledge', [Admin\KnowledgeAdminController::class, 'index'])->name('knowledge.index');
                // Reviewers (e.g. legal) can open an article to check it; only content managers create and edit.
                Route::get('knowledge/{article:id}/edit', [Admin\KnowledgeAdminController::class, 'edit'])->name('knowledge.edit');
                Route::middleware('permission:knowledge.manage')->group(function () {
                    Route::get('knowledge/create', [Admin\KnowledgeAdminController::class, 'create'])->name('knowledge.create');
                    Route::post('knowledge', [Admin\KnowledgeAdminController::class, 'store'])->name('knowledge.store');
                    Route::put('knowledge/{article:id}', [Admin\KnowledgeAdminController::class, 'update'])->name('knowledge.update');
                });
                Route::post('knowledge/{article:id}/status', [Admin\KnowledgeAdminController::class, 'status'])->name('knowledge.status');
            });

            Route::middleware('permission:pilot.manage|reports.view')->group(function () {
                Route::get('pilot', [Admin\PilotController::class, 'show'])->name('pilot.show');
                Route::put('pilot', [Admin\PilotController::class, 'update'])->middleware('permission:pilot.manage')->name('pilot.update');
                Route::post('pilot/gates/{gate}', [Admin\PilotController::class, 'decide'])->middleware('permission:pilot.manage')->name('pilot.gates.decide');
                Route::post('pilot/reports', [Admin\PilotController::class, 'generateReport'])->middleware('permission:pilot.manage')->name('pilot.reports.generate');
                Route::get('pilot/reports/{report}', [Admin\PilotController::class, 'report'])->name('pilot.reports.show');
                Route::put('pilot/reports/{report}', [Admin\PilotController::class, 'notes'])->middleware('permission:pilot.manage')->name('pilot.reports.notes');
                Route::get('pilot/reports/{report}/export', [Admin\PilotController::class, 'export'])->name('pilot.reports.export');
                Route::post('businesses/{business}/eligibility', [Admin\PilotController::class, 'eligibility'])->middleware('permission:pilot.manage')->name('businesses.eligibility');
            });

            Route::middleware('permission:partners.manage')->group(function () {
                Route::get('partners', [Admin\PartnerController::class, 'index'])->name('partners.index');
                Route::post('partners', [Admin\PartnerController::class, 'store'])->name('partners.store');
                Route::put('partners/{partner}', [Admin\PartnerController::class, 'update'])->name('partners.update');
            });

            Route::middleware('permission:knowledge.manage')->group(function () {
                Route::get('knowledge-taxonomy', [Admin\KnowledgeTaxonomyController::class, 'index'])->name('taxonomy.index');
                Route::post('knowledge-taxonomy/categories', [Admin\KnowledgeTaxonomyController::class, 'storeCategory'])->name('taxonomy.categories.store');
                Route::put('knowledge-taxonomy/categories/{category}', [Admin\KnowledgeTaxonomyController::class, 'updateCategory'])->name('taxonomy.categories.update');
                Route::post('knowledge-taxonomy/sources', [Admin\KnowledgeTaxonomyController::class, 'storeSource'])->name('taxonomy.sources.store');
                Route::put('knowledge-taxonomy/sources/{source}', [Admin\KnowledgeTaxonomyController::class, 'updateSource'])->name('taxonomy.sources.update');
            });

            Route::middleware('permission:legal.review|data_requests.manage|complaints.manage')->group(function () {
                Route::get('compliance', [Admin\ComplianceController::class, 'index'])->name('compliance.index');
                Route::post('compliance/requests/{collaboration}', [Admin\ComplianceController::class, 'decideRequest'])->name('compliance.requests.decide');
                Route::put('compliance/paths/{path}', [Admin\ComplianceController::class, 'updatePath'])->name('compliance.paths.update');
                Route::post('compliance/data-requests/{dataRequest}', [Admin\ComplianceController::class, 'decideDataRequest'])->name('compliance.data.decide');
                Route::put('compliance/complaints/{complaint}', [Admin\ComplianceController::class, 'updateComplaint'])->name('compliance.complaints.update');
            });

            Route::get('audit', Admin\AuditLogController::class)->middleware('permission:audit.view')->name('audit.index');
        });
    });
});

// Unknown addresses still run through the web middleware (session, user), so the 404 page rendered by the
// exception handler has the visitor's language and the normal public layout (top bar, hamburger, tab bar).
Route::fallback(fn () => abort(404));
