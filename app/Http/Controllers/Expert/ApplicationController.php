<?php

namespace App\Http\Controllers\Expert;

use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Experts\Actions\SaveExpertProfile;
use App\Domain\Experts\Actions\SubmitExpertApplication;
use App\Domain\Experts\Enums\ExpertVerificationStatus;
use App\Domain\Experts\Models\ExpertDocument;
use App\Domain\Experts\Models\ExpertProfile;
use App\Http\Controllers\Controller;
use App\Services\Files\SecureFileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Expert membership application and profile (skills, languages, availability, NDA, documents). */
class ApplicationController extends Controller
{
    public function edit(Request $request): Response
    {
        $profile = $request->user()->expertProfile?->load(['skills', 'languages', 'availability', 'documents', 'verifications.reviewer']);

        return Inertia::render('Expert/Profile', [
            'profile' => $profile ? array_merge($profile->only([
                'supporter_type', 'organization_name', 'support_models', 'headline', 'bio', 'country', 'city', 'timezone', 'years_experience', 'industries', 'serves_countries', 'collaboration_types',
                'certifications', 'linkedin_url', 'max_active_cases', 'is_available',
            ]), [
                'verification_status' => $profile->verification_status->value,
                'nda_accepted_at' => $profile->nda_accepted_at?->toIso8601String(),
                'skills' => $profile->skills->map->only(['case_category_id', 'level', 'years'])->all(),
                'languages' => $profile->languages->map->only(['language', 'proficiency'])->all(),
                'availability' => $profile->availability->map(fn ($a) => ['weekday' => $a->weekday, 'starts_at' => substr($a->starts_at, 0, 5), 'ends_at' => substr($a->ends_at, 0, 5)])->all(),
                'documents' => $profile->documents->map(fn ($d) => $d->fileSummary() + ['type' => $d->type])->all(),
                'feedback' => $profile->verifications->firstWhere('notes', '!=', null)?->notes,
                'privacy' => $profile->privacyMap(),
            ]) : null,
            'categories' => CaseCategory::where('is_active', true)->where('slug', '!=', 'general')->orderBy('sort_order')->get()->map->toOption(),
            'collaborationTypes' => ExpertProfile::COLLABORATION_TYPES,
            'supportModels' => ExpertProfile::SUPPORT_MODELS,
            'defaultPrivacy' => ExpertProfile::defaultPrivacy(),
        ]);
    }

    public function update(Request $request, SaveExpertProfile $action): RedirectResponse
    {
        $data = $request->validate(SaveExpertProfile::rules());
        $action->handle($request->user(), $data);

        return back()->with('success', __('experts.saved'));
    }

    public function submit(Request $request, SubmitExpertApplication $action): RedirectResponse
    {
        $request->validate(['nda' => ['accepted']]);
        $profile = $request->user()->expertProfile()->first();
        abort_unless($profile && in_array($profile->verification_status, [ExpertVerificationStatus::Draft, ExpertVerificationStatus::Rejected], true), 409);
        $action->handle($profile, $request->user());

        return back()->with('success', __('experts.submitted'));
    }

    public function uploadDocument(Request $request, SecureFileStorage $files): RedirectResponse
    {
        $profile = $request->user()->expertProfile()->first();
        abort_unless($profile, 409);
        $data = $request->validate(['file' => ['required', ...SecureFileStorage::documentRules()], 'type' => ['required', 'in:certificate,cv,id,portfolio,other'], 'title' => ['nullable', 'string', 'max:150']]);
        $doc = ExpertDocument::create($files->store($request->file('file'), "experts/{$profile->id}") + [
            'expert_profile_id' => $profile->id, 'uploaded_by' => $request->user()->id, 'type' => $data['type'], 'title' => $data['title'] ?? null,
        ]);
        $files->scan($doc);

        return back()->with('success', __('app.saved'));
    }
}
