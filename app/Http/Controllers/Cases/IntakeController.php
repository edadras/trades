<?php

namespace App\Http\Controllers\Cases;

use App\Domain\Cases\Actions\AnswerIntakeQuestion;
use App\Domain\Cases\Actions\SubmitCase;
use App\Domain\Cases\Actions\UploadCaseDocument;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\SupportCase;
use App\Http\Controllers\Controller;
use App\Services\Files\SecureFileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** The AI follow-up conversation of a draft case, then submission. */
class IntakeController extends Controller
{
    public function answer(Request $request, SupportCase $case, AnswerIntakeQuestion $intake, UploadCaseDocument $upload): RedirectResponse
    {
        $this->authorizeDraft($request, $case);
        $data = $request->validate([
            'key' => ['required', 'string', 'max:60'],
            'answer' => ['nullable', 'string', 'max:4000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => SecureFileStorage::documentRules(),
        ]);
        foreach ($request->file('attachments', []) as $file) {
            $upload->handle($case, $request->user(), $file);
        }
        $intake->answer($case, $request->user(), $data['key'], $data['answer'] ?? null);

        return redirect()->route('cases.create', ['case' => $case->number]);
    }

    public function submit(Request $request, SupportCase $case, AnswerIntakeQuestion $intake, SubmitCase $submit): RedirectResponse
    {
        $this->authorizeDraft($request, $case);
        $intake->skipRemaining($case, $request->user());
        $submit->handle($case->fresh());

        return redirect()->route('cases.show', $case->number)->with('success', __('cases.submitted'));
    }

    private function authorizeDraft(Request $request, SupportCase $case): void
    {
        Gate::authorize('update', $case);
        abort_unless($case->status === CaseStatus::Draft, 409);
    }
}
