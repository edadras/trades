<?php

namespace App\Http\Controllers\Support;

use App\Domain\Cases\Models\SupportCase;
use App\Domain\Compliance\Actions\RecordComplaint;
use App\Domain\Compliance\Models\Complaint;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** Complaints and support requests from any user (businesses and experts). */
class SupportController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $cases = $user->currentBusiness()?->cases()->real()->latest()->get(['id', 'number', 'title'])
            ?? $user->expertProfile?->cases()->get(['cases.id', 'number', 'title']) ?? collect();

        return Inertia::render('Support/Index', [
            'complaints' => Complaint::with('case')->where('user_id', $user->id)->latest()->get()->map->toCard(),
            'cases' => $cases->map(fn ($c) => ['value' => $c->id, 'label' => $c->number.' — '.str($c->title)->limit(40)])->values(),
            'categories' => Complaint::CATEGORIES,
        ]);
    }

    public function store(Request $request, RecordComplaint $action): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', Rule::in(Complaint::CATEGORIES)],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
            'case_id' => ['nullable', 'integer', 'exists:cases,id'],
        ]);
        if (! empty($data['case_id'])) {
            Gate::authorize('view', SupportCase::findOrFail($data['case_id']));
        }
        $action->handle($request->user(), $data);

        return back()->with('success', __('support.submitted'));
    }
}
