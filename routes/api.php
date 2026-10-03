<?php

use App\Domain\Cases\Models\SupportCase;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Presenters\CasePresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

/*
| Token API (Sanctum) for integrations and future mobile clients. Read-only in this release;
| all writes go through the same Actions used by the web app.
*/
Route::prefix('v1')->middleware(['auth:sanctum', EnsureActiveUser::class, 'throttle:api'])->group(function () {
    Route::get('me', fn (Request $request) => $request->user()->only(['id', 'name', 'email', 'locale']) + ['roles' => $request->user()->getRoleNames()]);

    Route::get('cases', function (Request $request, CasePresenter $presenter) {
        $business = $request->user()->currentBusiness();
        abort_unless($business, 403);

        return $business->cases()->with(['category', 'subcategory'])->latest()->paginate(20)->through(fn ($c) => $presenter->card($c));
    });

    Route::get('cases/{case}', function (Request $request, SupportCase $case, CasePresenter $presenter) {
        Gate::authorize('view', $case);

        return $presenter->card($case->load(['category', 'subcategory'])) + ['stepper' => $presenter->stepper($case)];
    });
});

Route::get('v1/knowledge', fn (Request $request) => KnowledgeArticle::approved()->with(['translations', 'category'])->latest('published_at')
    ->paginate(20)->through(fn ($a) => $a->toCard($request->query('locale', 'fa'))))->middleware('throttle:api');
