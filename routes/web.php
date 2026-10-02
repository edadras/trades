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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
        });

        // ── Business panel ───────────────────────────────────────────────
        Route::middleware('role:business')->group(function () {
            Route::get('onboarding', [Business\OnboardingController::class, 'show'])->name('onboarding.show');
            Route::post('onboarding', [Business\OnboardingController::class, 'store'])->middleware('throttle:uploads')->name('onboarding.store');

            Route::middleware('onboarded')->group(function () {
                Route::get('dashboard', Business\DashboardController::class)->name('dashboard');
                Route::get('learning', Business\LearningController::class)->name('learning');
                Route::get('business/profile', [Business\BusinessProfileController::class, 'edit'])->name('business.profile');
                Route::put('business/profile', [Business\BusinessProfileController::class, 'update'])->name('business.profile.update');
                Route::post('business/documents', [Business\BusinessProfileController::class, 'uploadDocument'])->middleware('throttle:uploads')->name('business.documents.store');
                Route::delete('business/documents/{document}', [Business\BusinessProfileController::class, 'destroyDocument'])->name('business.documents.destroy');

                Route::get('cases', [Cases\CaseController::class, 'index'])->name('cases.index');
                Route::get('cases/new', [Cases\CaseController::class, 'create'])->name('cases.create');
                Route::post('cases', [Cases\CaseController::class, 'store'])->middleware('throttle:ai')->name('cases.store');
                Route::post('cases/{case}/intake', [Cases\IntakeController::class, 'answer'])->middleware('throttle:ai')->name('cases.intake.answer');
                Route::post('cases/{case}/submit', [Cases\IntakeController::class, 'submit'])->middleware('throttle:ai')->name('cases.submit');
                Route::post('cases/{case}/matches/{match}', [Cases\MatchController::class, 'decide'])->name('cases.matches.decide');
                Route::post('cases/{case}/satisfaction', [Cases\OutcomeController::class, 'satisfaction'])->name('cases.satisfaction');
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

        // ── Expert review panel (internal case experts) ─────────────────
        Route::prefix('review')->name('review.')->middleware(['staff', 'permission:cases.review'])->group(function () {
            Route::get('/', [Review\ReviewQueueController::class, 'index'])->name('index');
            Route::get('cases', [Review\ReviewQueueController::class, 'cases'])->name('cases.index');
            Route::get('cases/{case}', [Cases\CaseController::class, 'show'])->name('cases.show');
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
                Route::get('knowledge/create', [Admin\KnowledgeAdminController::class, 'create'])->name('knowledge.create');
                Route::post('knowledge', [Admin\KnowledgeAdminController::class, 'store'])->name('knowledge.store');
                Route::get('knowledge/{article:id}/edit', [Admin\KnowledgeAdminController::class, 'edit'])->name('knowledge.edit');
                Route::put('knowledge/{article:id}', [Admin\KnowledgeAdminController::class, 'update'])->name('knowledge.update');
                Route::post('knowledge/{article:id}/status', [Admin\KnowledgeAdminController::class, 'status'])->name('knowledge.status');
            });

            Route::get('audit', Admin\AuditLogController::class)->middleware('permission:audit.view')->name('audit.index');
        });
    });
});
