<?php

namespace App\Http\Controllers\Expert;

use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\Appointment;
use App\Domain\Cases\Models\CaseTask;
use App\Domain\Matching\Enums\MatchStatus;
use App\Http\Controllers\Controller;
use App\Http\Presenters\CasePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CasePresenter $presenter): Response|RedirectResponse
    {
        $user = $request->user();
        $profile = $user->expertProfile;
        if (! $profile) {
            return redirect()->route('expert.profile.edit');
        }

        $cases = $profile->cases()->with(['category', 'subcategory', 'business'])->latest('cases.updated_at')->get();
        $active = $cases->filter(fn ($c) => $c->pivot->status === 'active' && ! in_array($c->status, [CaseStatus::Closed], true));
        $caseIds = $active->pluck('id');

        return Inertia::render('Expert/Dashboard', [
            'profile' => ['status' => $profile->verification_status->value, 'headline' => $profile->headline, 'is_available' => $profile->is_available],
            'stats' => [
                'invitations' => $profile->matches()->where('status', MatchStatus::Invited->value)->count(),
                'active_cases' => $active->count(),
                'pending_tasks' => CaseTask::whereIn('case_id', $caseIds)->pending()->where(fn ($q) => $q->where('assignee_id', $user->id)->orWhere('owner_role', 'expert'))->count(),
                'upcoming_meetings' => Appointment::whereIn('case_id', $caseIds)->where('starts_at', '>=', now())->count(),
                'response_hours' => $profile->avg_response_minutes ? round($profile->avg_response_minutes / 60, 1) : null,
                'completed_cases' => $cases->filter(fn ($c) => in_array($c->status, [CaseStatus::Resolved, CaseStatus::Closed], true))->count(),
            ],
            'cases' => $active->map(fn ($c) => $presenter->card($c) + ['stepper' => $presenter->stepper($c)])->values(),
            'tasks' => CaseTask::whereIn('case_id', $caseIds)->pending()->where(fn ($q) => $q->where('assignee_id', $user->id)->orWhere('owner_role', 'expert'))
                ->with('case:id,number')->orderBy('due_at')->limit(8)->get()->map(fn ($t) => $t->toCard() + ['case_number' => $t->case->number]),
            'appointments' => Appointment::whereIn('case_id', $caseIds)->where('starts_at', '>=', now()->subHour())->with(['case:id,number', 'organizer:id,name'])->orderBy('starts_at')->limit(5)->get()->map->toCard(),
        ]);
    }
}
