<?php

namespace App\Domain\Pilot\Actions;

use App\Domain\AI\Models\AiHumanReview;
use App\Domain\Analytics\KpiService;
use App\Domain\Business\Models\Business;
use App\Domain\Cases\Models\CaseOutcome;
use App\Domain\Cases\Models\SatisfactionSurvey;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Compliance\Models\Complaint;
use App\Domain\Identity\Enums\Permission;
use App\Domain\Pilot\Models\AiIncident;
use App\Domain\Pilot\Models\PilotProgram;
use App\Domain\Pilot\Models\PilotReport;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Weekly report of results and errors for the pilot team. */
class GeneratePilotReport
{
    public function __construct(private readonly KpiService $kpis) {}

    public function handle(?CarbonImmutable $weekStart = null, ?User $author = null, bool $notify = true): PilotReport
    {
        $start = ($weekStart ?? CarbonImmutable::now()->subWeek())->startOfWeek(CarbonImmutable::SATURDAY);
        $end = $start->endOfWeek(CarbonImmutable::FRIDAY);
        $range = [$start, $end];
        $sla = (int) config('platform.initial_review_sla_hours');

        $submitted = SupportCase::real()->whereBetween('submitted_at', $range)->get();
        $metrics = [
            'new_businesses' => Business::whereBetween('onboarding_completed_at', $range)->count(),
            'cases_submitted' => $submitted->count(),
            'cases_by_category' => $submitted->load('category')->groupBy(fn ($c) => $c->category?->slug ?? 'unclassified')->map->count()->all(),
            'cases_resolved' => SupportCase::whereBetween('resolved_at', $range)->count(),
            'cases_closed' => SupportCase::whereBetween('closed_at', $range)->count(),
            'outcomes' => CaseOutcome::whereBetween('created_at', $range)->whereNull('superseded_at')->get()->groupBy(fn ($o) => $o->outcome->value)->map->count()->all(),
            'reviews_completed' => AiHumanReview::where('status', 'completed')->whereBetween('reviewed_at', $range)->count(),
            'satisfaction_avg' => ($avg = SatisfactionSurvey::whereBetween('created_at', $range)->avg('rating')) ? round((float) $avg, 2) : null,
            'kpis' => $this->kpis->evaluate()->map(fn ($k) => ['key' => $k['key'], 'value' => $k['value'], 'target' => $k['target'], 'achieved' => $k['achieved']])->values()->all(),
        ];

        $corrections = AiHumanReview::with(['case', 'aiCategory', 'finalCategory'])->where('status', 'completed')
            ->whereBetween('reviewed_at', $range)->where(fn ($q) => $q->where('category_agreed', false)->orWhere('urgency_agreed', false))->get();
        $errors = [
            'ai_corrections' => $corrections->map(fn ($r) => [
                'case' => $r->case?->number, 'ai' => $r->aiCategory?->slug, 'final' => $r->finalCategory?->slug,
                'ai_urgency' => $r->ai_urgency, 'final_urgency' => $r->final_urgency,
            ])->values()->all(),
            'ai_incidents' => AiIncident::whereBetween('created_at', $range)->selectRaw('operation, count(*) as c')->groupBy('operation')->pluck('c', 'operation')->all(),
            'failed_jobs' => DB::table('failed_jobs')->whereBetween('failed_at', $range)->count(),
            'sla_breaches' => $submitted->filter(function ($c) use ($sla) {
                $decided = collect([$c->first_reviewed_at, $c->ready_at])->filter()->min();

                return $decided ? $c->submitted_at->diffInHours($decided) > $sla : $c->submitted_at->diffInHours(now()) > $sla;
            })->pluck('number')->values()->all(),
            'outcome_disputes' => Complaint::where('category', 'outcome_dispute')->whereBetween('created_at', $range)->count(),
            'complaints' => Complaint::whereBetween('created_at', $range)->count(),
            'dissatisfaction' => SatisfactionSurvey::whereBetween('created_at', $range)->whereNotNull('dissatisfaction_reason')
                ->selectRaw('dissatisfaction_reason, count(*) as c')->groupBy('dissatisfaction_reason')->pluck('c', 'dissatisfaction_reason')->all(),
        ];

        $report = PilotReport::updateOrCreate(['week_start' => $start->toDateString()], [
            'pilot_program_id' => PilotProgram::active()?->id,
            'week_end' => $end->toDateString(),
            'metrics' => $metrics,
            'errors' => $errors,
            'author_id' => $author?->id,
        ]);

        if ($notify) {
            foreach (User::permission(Permission::ReportsView->value)->get() as $user) {
                $user->notify(new PlatformNotification('pilot_report_ready', ['week' => $start->toDateString()], route('admin.pilot.reports.show', ['locale' => $user->locale ?: 'fa', 'report' => $report->id])));
            }
        }

        return $report;
    }
}
