<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Experts\Actions\VerifyExpert;
use App\Domain\Experts\Enums\ExpertVerificationStatus;
use App\Domain\Experts\Models\ExpertProfile;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ExpertController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(array_column(ExpertVerificationStatus::cases(), 'value'))]])['status'] ?? null;
        $experts = ExpertProfile::with(['user', 'languages', 'categories'])
            ->when($status, fn ($q) => $q->where('verification_status', $status))
            ->orderByRaw("case verification_status when 'submitted' then 0 when 'in_review' then 1 else 2 end")->latest()
            ->paginate(20)->withQueryString()
            ->through(fn ($e) => $e->toCard() + ['status' => $e->verification_status->value, 'email' => $e->user->email, 'active_cases' => $e->activeCaseCount()]);

        return Inertia::render('Admin/Experts', ['experts' => $experts, 'filters' => ['status' => $status]]);
    }

    public function show(ExpertProfile $expert): Response
    {
        $expert->load(['user', 'languages', 'categories', 'availability', 'documents', 'verifications.reviewer']);

        return Inertia::render('Admin/ExpertShow', [
            'expert' => $expert->toCard() + [
                'status' => $expert->verification_status->value,
                'email' => $expert->user->email,
                'linkedin_url' => $expert->linkedin_url,
                'certifications' => $expert->certifications ?? [],
                'serves_countries' => $expert->serves_countries ?? [],
                'max_active_cases' => $expert->max_active_cases,
                'nda_accepted_at' => $expert->nda_accepted_at?->toIso8601String(),
                'availability' => $expert->availability->map(fn ($a) => ['weekday' => $a->weekday, 'starts_at' => substr($a->starts_at, 0, 5), 'ends_at' => substr($a->ends_at, 0, 5)])->all(),
                'documents' => $expert->documents->map(fn ($d) => $d->fileSummary() + ['type' => $d->type])->all(),
                'verifications' => $expert->verifications->map(fn ($v) => ['status' => $v->status, 'notes' => $v->notes, 'checklist' => $v->checklist, 'reviewer' => $v->reviewer?->name, 'created_at' => $v->created_at->toIso8601String()])->all(),
            ],
        ]);
    }

    public function verify(Request $request, ExpertProfile $expert, VerifyExpert $action): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['in_review', 'verified', 'rejected', 'suspended'])],
            'checklist' => ['array'], 'checklist.*' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $action->handle($expert, $request->user(), ExpertVerificationStatus::from($data['status']), $data['checklist'] ?? [], $data['notes'] ?? null);

        return back()->with('success', __('experts.verified'));
    }
}
