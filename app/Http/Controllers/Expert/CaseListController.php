<?php

namespace App\Http\Controllers\Expert;

use App\Http\Controllers\Controller;
use App\Http\Presenters\CasePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CaseListController extends Controller
{
    public function __invoke(Request $request, CasePresenter $presenter): Response
    {
        $profile = $request->user()->expertProfile;
        abort_unless($profile, 403);
        $cases = $profile->cases()->with(['category', 'subcategory', 'business'])->latest('cases.updated_at')->paginate(12)
            ->through(fn ($c) => $presenter->card($c) + ['stepper' => $presenter->stepper($c), 'membership' => $c->pivot->status]);

        return Inertia::render('Expert/Cases', ['cases' => $cases]);
    }
}
