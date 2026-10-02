<?php

namespace App\Http\Controllers\Business;

use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\Appointment;
use App\Domain\Cases\Models\CaseTask;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Knowledge\Models\KnowledgeArticle;
use App\Domain\Messaging\Models\Conversation;
use App\Http\Controllers\Controller;
use App\Http\Presenters\CasePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, CasePresenter $presenter): Response
    {
        $user = $request->user();
        $business = $user->currentBusiness();
        $cases = $business->cases()->with(['category', 'subcategory'])->latest('updated_at')->get();
        $caseIds = $cases->pluck('id');

        $tasks = CaseTask::whereIn('case_id', $caseIds)->pending()->where(fn ($q) => $q->where('assignee_id', $user->id)->orWhere('owner_role', 'business'))
            ->with(['case:id,number', 'assignee:id,name'])->orderByRaw('due_at is null')->orderBy('due_at')->limit(8)->get()
            ->map(fn ($t) => $t->toCard() + ['case_number' => $t->case->number]);

        $appointments = Appointment::whereIn('case_id', $caseIds)->where('starts_at', '>=', now()->subHour())->where('status', 'scheduled')
            ->with(['case:id,number', 'organizer:id,name'])->orderBy('starts_at')->limit(5)->get()->map->toCard();

        $recommended = KnowledgeArticle::approved()
            ->where(fn ($w) => $w->whereHas('problemTypes', fn ($q) => $q->whereIn('case_categories.id', $cases->pluck('category_id')->merge($cases->pluck('subcategory_id'))->filter()))
                ->orWhereJsonContains('industries', $business->industry ?? '__none__'))
            ->with(['translations', 'category'])->latest('published_at')->limit(4)->get()->map->toCard();
        if ($recommended->isEmpty()) {
            $recommended = KnowledgeArticle::approved()->with(['translations', 'category'])->where('is_featured', true)->limit(4)->get()->map->toCard();
        }

        $conversations = Conversation::whereIn('case_id', $caseIds)->whereNotNull('last_message_at')->with('case:id,number,title')
            ->latest('last_message_at')->limit(5)->get()->map(fn ($c) => [
                'id' => $c->id, 'case_number' => $c->case->number, 'case_title' => $c->case->title,
                'unread' => $c->unreadCountFor($user), 'last_message_at' => $c->last_message_at->toIso8601String(),
            ]);

        return Inertia::render('Business/Dashboard', [
            'business' => ['name' => $business->trade_name, 'industry' => $business->industry, 'eligibility' => $business->eligibility_status, 'eligibility_reason' => $business->eligibility_reason, 'can_open_cases' => $business->canOpenCases(), 'role' => $business->roleOf($user)],
            'pendingOutcomes' => SupportCase::whereIn('id', $caseIds)->whereHas('outcome', fn ($q) => $q->where('confirmation_status', 'pending'))->get(['id', 'number', 'title'])->map->only(['number', 'title']),
            'stats' => [
                'active_cases' => $cases->filter(fn ($c) => $c->status->isOpen())->count(),
                'waiting_actions' => CaseTask::whereIn('case_id', $caseIds)->pending()->where('owner_role', 'business')->count(),
                'expert_sessions' => Appointment::whereIn('case_id', $caseIds)->where('starts_at', '>=', now())->count(),
                'recommendations' => $recommended->count(),
            ],
            'cases' => $cases->take(6)->map(fn ($c) => $presenter->card($c) + ['stepper' => $presenter->stepper($c)])->values(),
            'drafts' => $cases->where('status', CaseStatus::Draft)->count(),
            'tasks' => $tasks,
            'appointments' => $appointments,
            'recommended' => $recommended,
            'conversations' => $conversations,
        ]);
    }
}
