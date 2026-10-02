<?php

namespace App\Http\Controllers\Cases;

use App\Domain\Business\Models\Partner;
use App\Domain\Cases\Actions\AnswerIntakeQuestion;
use App\Domain\Cases\Actions\CreateCase;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Compliance\Models\ServicePath;
use App\Domain\Identity\AuditLogger;
use App\Domain\Messaging\Actions\MarkConversationRead;
use App\Http\Controllers\Controller;
use App\Http\Presenters\CasePresenter;
use App\Services\Files\SecureFileStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class CaseController extends Controller
{
    public function __construct(private readonly CasePresenter $presenter) {}

    public function index(Request $request): Response
    {
        $business = $request->user()->currentBusiness();
        $filters = $request->validate(['status' => ['nullable', 'in:open,closed,draft,all']]);
        $status = $filters['status'] ?? 'all';

        $cases = $business->cases()->with(['category', 'subcategory'])
            ->when($status === 'open', fn ($q) => $q->open())
            ->when($status === 'closed', fn ($q) => $q->whereIn('status', [CaseStatus::Resolved->value, CaseStatus::Closed->value]))
            ->when($status === 'draft', fn ($q) => $q->where('status', CaseStatus::Draft->value))
            ->latest('updated_at')->paginate(12)->withQueryString()
            ->through(fn ($c) => $this->presenter->card($c) + ['stepper' => $this->presenter->stepper($c)]);

        return Inertia::render('Cases/Index', ['cases' => $cases, 'filters' => ['status' => $status]]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', SupportCase::class);
        $draft = $request->query('case') ? $request->user()->currentBusiness()->cases()->where('number', $request->query('case'))->where('status', CaseStatus::Draft->value)->first() : null;

        return Inertia::render('Cases/Create', [
            'draft' => $draft ? [
                'number' => $draft->number,
                'description' => $draft->description,
                'has_voice' => (bool) $draft->voice_path,
                'answers' => $draft->answers->map(fn ($a) => ['key' => $a->question_key, 'question' => $a->question, 'answer' => $a->answer])->all(),
                'documents' => $draft->documents->map->fileSummary()->all(),
            ] : null,
            'example' => __('ai.disclaimer'),
            'partners' => $request->user()->currentBusiness()->partner_id ? [] : Partner::where('is_active', true)->get()->map(fn ($p) => ['value' => $p->id, 'label' => $p->translate('name')]),
            'maxUploadKb' => config('platform.uploads.max_kb'),
            'accept' => collect(config('platform.uploads.mimes'))->map(fn ($m) => '.'.$m)->implode(','),
        ]);
    }

    /** Step 1 of intake: the free-form problem (text and/or voice + attachments) opens a draft case and returns the first AI question. */
    public function store(Request $request, CreateCase $create, AnswerIntakeQuestion $intake): RedirectResponse
    {
        Gate::authorize('create', SupportCase::class);
        $data = $request->validate([
            'description' => ['nullable', 'required_without:voice', 'string', 'max:10000'],
            'voice' => ['nullable', ...SecureFileStorage::voiceRules()],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => SecureFileStorage::documentRules(),
            'actions_taken' => ['nullable', 'string', 'max:4000'],
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
            'consent_ai_processing' => ['boolean'],
            'consent_share_with_foreign_experts' => ['boolean'],
            'consent_anonymized_learning' => ['boolean'],
        ]);

        $case = $create->handle(
            $request->user(), $request->user()->currentBusiness(), $data['description'] ?? null, $request->file('voice'), $request->file('attachments', []), app()->getLocale(),
            [
                'ai_processing' => $request->boolean('consent_ai_processing', true),
                'share_with_foreign_experts' => $request->boolean('consent_share_with_foreign_experts', true),
                'anonymized_learning' => $request->boolean('consent_anonymized_learning'),
            ],
            ['actions_taken' => $data['actions_taken'] ?? null, 'partner_id' => $data['partner_id'] ?? null],
        );
        $intake->next($case, $request->user());

        return redirect()->route('cases.create', ['case' => $case->number]);
    }

    public function show(Request $request, SupportCase $case, AuditLogger $audit): Response
    {
        Gate::authorize('view', $case);
        $user = $request->user();
        $context = match (true) {
            $case->business->hasMember($user) => 'business',
            $case->hasActiveExpert($user) => 'expert',
            default => 'staff',
        };
        if ($context !== 'business') {
            $audit->log('case.viewed', $case, ['context' => $context]);
        }
        if ($case->conversation && $case->conversation->hasMember($user)) {
            app(MarkConversationRead::class)->handle($case->conversation, $user);
        }

        return Inertia::render('Cases/Show', [
            'case' => $this->presenter->show($case, $user, $context),
            'tab' => $request->query('tab', 'overview'),
            'categories' => $context === 'staff' ? CaseCategory::where('is_active', true)->orderBy('sort_order')->get()->map->toOption() : [],
            'servicePaths' => ServicePath::orderBy('sort_order')->get()->map->toOption(),
        ]);
    }

    /** Streams the original voice note of the problem to those allowed to see the case. */
    public function voice(Request $request, SupportCase $case, AuditLogger $audit)
    {
        Gate::authorize('view', $case);
        abort_unless($case->voice_path, 404);
        $audit->log('case.voice_played', $case);

        return Storage::disk(config('platform.uploads.disk'))->response($case->voice_path, $case->number.'.'.pathinfo($case->voice_path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
