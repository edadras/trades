<?php

namespace App\Http\Controllers\Expert;

use App\Domain\Matching\Actions\RespondToInvitation;
use App\Domain\Matching\Enums\MatchStatus;
use App\Domain\Matching\Models\ExpertMatch;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Invitations show only an anonymised case summary. Business identity, contacts and documents
 * become visible only after the expert accepts.
 */
class InvitationController extends Controller
{
    public function index(Request $request): Response
    {
        $profile = $request->user()->expertProfile;
        abort_unless($profile, 403);

        $invitations = $profile->matches()->with(['case.category', 'case.subcategory', 'case.business', 'case.latestAnalysis'])
            ->whereIn('status', [MatchStatus::Invited->value, MatchStatus::Active->value, MatchStatus::ExpertDeclined->value])
            ->latest('business_decided_at')->get()
            ->map(fn (ExpertMatch $m) => [
                'id' => $m->id,
                'status' => $m->status->value,
                'score' => $m->score,
                'reasons' => $m->localizedReasons(),
                'invited_at' => $m->business_decided_at?->toIso8601String(),
                'case' => [
                    'number' => $m->case->number,
                    'category' => $m->case->category?->translate('name'),
                    'subcategory' => $m->case->subcategory?->translate('name'),
                    'urgency' => $m->case->urgency?->value,
                    'summary' => $this->anonymise((string) $m->case->summary, $m->case),
                    'business' => $m->case->business->anonymousProfile(),
                    'required_expertises' => $m->case->latestAnalysis?->required_expertises ?? [],
                ],
            ]);

        return Inertia::render('Expert/Invitations', ['invitations' => $invitations]);
    }

    public function respond(Request $request, ExpertMatch $match, RespondToInvitation $action): RedirectResponse
    {
        abort_unless($match->expertProfile->user_id === $request->user()->id, 403);
        $data = $request->validate(['accept' => ['required', 'boolean'], 'reason' => ['nullable', 'string', 'max:500']]);
        $action->handle($match, $request->user(), $data['accept'], $data['reason'] ?? null);

        return $data['accept']
            ? redirect()->route('expert.cases.show', $match->case->number)->with('success', __('app.saved'))
            : back()->with('success', __('app.saved'));
    }

    /** Removes the business name and direct identifiers from the summary shown before acceptance. */
    private function anonymise(string $text, $case): string
    {
        $text = app(\App\Domain\AI\Safety\PiiRedactor::class)->redact($text);
        foreach (array_filter([$case->business->trade_name, $case->business->legal_name]) as $name) {
            $text = Str::replace($name, '███', $text);
        }

        return $text;
    }
}
