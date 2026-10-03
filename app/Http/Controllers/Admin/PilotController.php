<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\KpiService;
use App\Domain\Analytics\Models\Kpi;
use App\Domain\Business\Models\Business;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Identity\AuditLogger;
use App\Domain\Pilot\Actions\EvaluateEligibility;
use App\Domain\Pilot\Actions\GeneratePilotReport;
use App\Domain\Pilot\Models\DecisionGate;
use App\Domain\Pilot\Models\PilotProgram;
use App\Domain\Pilot\Models\PilotReport;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Pilot programme scope, first-month decisions, decision gates (weeks 6/16/24) and weekly reports. */
class PilotController extends Controller
{
    public function show(): Response
    {
        $program = PilotProgram::active() ?? PilotProgram::latest('id')->first();
        $program?->ensureGates();

        return Inertia::render('Admin/Pilot', [
            'program' => $program ? $program->only(['id', 'status', 'regions', 'value_chains', 'industries', 'business_sizes', 'max_groups', 'priority_category_ids', 'max_businesses']) + [
                'name' => $program->translations('name'),
                'eligibility_notes' => $program->translations('eligibility_notes'),
                'success_definition' => $program->translations('success_definition'),
                'support_model_policy' => $program->translations('support_model_policy'),
                'partner_coordination' => $program->translations('partner_coordination'),
                'starts_on' => $program->starts_on?->toDateString(),
                'ends_on' => $program->ends_on?->toDateString(),
                'current_week' => $program->currentWeek(),
                'admitted' => Business::where('pilot_program_id', $program->id)->where('eligibility_status', 'eligible')->count(),
                'waitlisted' => Business::where('pilot_program_id', $program->id)->where('eligibility_status', 'waitlisted')->count(),
                'ineligible' => Business::where('pilot_program_id', $program->id)->where('eligibility_status', 'ineligible')->count(),
            ] : null,
            'gates' => $program ? $program->gates()->with('decider:id,name')->get()->map(fn (DecisionGate $g) => [
                'id' => $g->id, 'key' => $g->key, 'week' => $g->week, 'due_on' => $g->due_on?->toDateString(), 'criteria' => $g->criteria,
                'decision' => $g->decision, 'root_cause' => $g->root_cause, 'notes' => $g->notes, 'evidence' => $g->evidence,
                'decided_by' => $g->decider?->name, 'decided_at' => $g->decided_at?->toIso8601String(), 'overdue' => $g->isOverdue(),
                'options' => DecisionGate::DECISIONS[$g->key],
            ]) : [],
            'reports' => PilotReport::latest('week_start')->limit(12)->get()->map(fn ($r) => [
                'id' => $r->id, 'week_start' => $r->week_start->toDateString(), 'week_end' => $r->week_end->toDateString(),
                'cases' => $r->metrics['cases_submitted'] ?? 0, 'corrections' => count($r->errors['ai_corrections'] ?? []), 'sla_breaches' => count($r->errors['sla_breaches'] ?? []),
            ]),
            'categories' => CaseCategory::where('is_active', true)->orderBy('sort_order')->get()->map->toOption(),
            'valueChains' => config('platform.value_chains'),
            'rootCauses' => DecisionGate::ROOT_CAUSES,
            'negative' => DecisionGate::NEGATIVE,
        ]);
    }

    public function update(Request $request, AuditLogger $audit, EvaluateEligibility $eligibility): RedirectResponse
    {
        $data = $request->validate([
            'name.fa' => ['required', 'string', 'max:150'], 'name.en' => ['required', 'string', 'max:150'],
            'status' => ['required', Rule::in(['draft', 'active', 'closed'])],
            'starts_on' => ['nullable', 'date'], 'ends_on' => ['nullable', 'date', 'after:starts_on'],
            'regions' => ['array'], 'regions.*' => ['string', 'max:60'],
            'value_chains' => ['array'], 'value_chains.*' => [Rule::in(array_keys(config('platform.value_chains')))],
            'industries' => ['array'], 'industries.*' => [Rule::in(config('platform.industries'))],
            'business_sizes' => ['array'], 'business_sizes.*' => [Rule::in(config('platform.company_sizes'))],
            'max_groups' => ['required', 'integer', 'min:1', 'max:10'],
            'max_businesses' => ['nullable', 'integer', 'min:1'],
            'priority_category_ids' => ['array', 'max:3'], 'priority_category_ids.*' => ['integer', 'exists:case_categories,id'],
            'eligibility_notes.*' => ['nullable', 'string', 'max:2000'],
            'success_definition.*' => ['nullable', 'string', 'max:2000'],
            'support_model_policy.*' => ['nullable', 'string', 'max:2000'],
            'partner_coordination.*' => ['nullable', 'string', 'max:2000'],
        ]);
        if (count($data['value_chains'] ?? []) > $data['max_groups']) {
            return back()->withErrors(['value_chains' => __('pilot.errors.too_many_groups', ['n' => $data['max_groups']])]);
        }

        $program = PilotProgram::latest('id')->first() ?? new PilotProgram;
        if ($data['status'] === 'active') {
            PilotProgram::where('status', 'active')->whereKeyNot($program->id ?? 0)->update(['status' => 'closed']);
        }
        $program->fill($data)->save();
        $program->ensureGates();
        if ($program->status === 'active') {
            $eligibility->reevaluateAll();
        }
        $audit->log('pilot.updated', null, ['program' => $program->id]);

        return back()->with('success', __('app.saved'));
    }

    public function decide(Request $request, DecisionGate $gate, KpiService $kpis, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'criteria' => ['array'], 'criteria.*' => ['boolean'],
            'decision' => ['required', Rule::in(array_merge(['pending'], DecisionGate::DECISIONS[$gate->key]))],
            'root_cause' => [Rule::requiredIf(fn () => in_array($request->input('decision'), DecisionGate::NEGATIVE, true)), 'nullable', Rule::in(DecisionGate::ROOT_CAUSES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $decided = $data['decision'] !== 'pending';
        $gate->update([
            'criteria' => array_intersect_key($data['criteria'] ?? [], array_flip(DecisionGate::CRITERIA[$gate->key])) + $gate->criteria,
            'decision' => $data['decision'],
            'root_cause' => $data['root_cause'] ?? null,
            'notes' => $data['notes'] ?? null,
            'evidence' => $decided ? ['kpis' => $kpis->evaluate()->map(fn ($row) => Arr::only($row, ['key', 'value', 'target', 'achieved']))->values()->all(), 'captured_at' => now()->toIso8601String()] : $gate->evidence,
            'decided_by' => $decided ? $request->user()->id : null,
            'decided_at' => $decided ? now() : null,
        ]);
        $audit->log('pilot.gate.'.$gate->key, null, ['decision' => $data['decision'], 'root_cause' => $data['root_cause'] ?? null]);

        return back()->with('success', __('app.saved'));
    }

    public function generateReport(Request $request, GeneratePilotReport $action): RedirectResponse
    {
        $data = $request->validate(['week_start' => ['nullable', 'date']]);
        $report = $action->handle(isset($data['week_start']) ? CarbonImmutable::parse($data['week_start']) : CarbonImmutable::now(), $request->user(), false);

        return redirect()->route('admin.pilot.reports.show', $report->id);
    }

    public function report(PilotReport $report): Response
    {
        return Inertia::render('Admin/PilotReport', ['report' => [
            'id' => $report->id, 'week_start' => $report->week_start->toDateString(), 'week_end' => $report->week_end->toDateString(),
            'metrics' => $report->metrics, 'errors' => $report->errors, 'notes' => $report->notes, 'author' => $report->author?->name,
            'created_at' => $report->created_at->toIso8601String(),
        ],
            'categoryNames' => CaseCategory::all()->mapWithKeys(fn (CaseCategory $c) => [$c->slug => $c->translate('name')]),
            'kpiNames' => Kpi::all()->mapWithKeys(fn (Kpi $k) => [$k->key => $k->translate('name')]),
        ]);
    }

    public function notes(Request $request, PilotReport $report): RedirectResponse
    {
        $report->update(['notes' => $request->validate(['notes' => ['nullable', 'string', 'max:10000']])['notes'], 'author_id' => $request->user()->id]);

        return back()->with('success', __('app.saved'));
    }

    public function export(PilotReport $report): StreamedResponse
    {
        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['section', 'key', 'value']);
            foreach ($report->metrics as $key => $value) {
                if ($key === 'kpis') {
                    foreach ($value as $k) {
                        fputcsv($out, ['kpi', $k['key'], ($k['value'] ?? '').' / '.($k['target'] ?? '')]);
                    }

                    continue;
                }
                fputcsv($out, ['metrics', $key, is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value]);
            }
            foreach ($report->errors as $key => $value) {
                fputcsv($out, ['errors', $key, is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value]);
            }
            fputcsv($out, ['notes', 'notes', $report->notes]);
            fclose($out);
        }, "pilot-report-{$report->week_start->toDateString()}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Staff override of a business's pilot eligibility. */
    public function eligibility(Request $request, Business $business, AuditLogger $audit): RedirectResponse
    {
        $data = $request->validate(['eligibility_status' => ['required', Rule::in(['eligible', 'waitlisted', 'ineligible'])], 'eligibility_reason' => ['nullable', 'string', 'max:500']]);
        // Staff decisions are marked so automatic re-evaluation (scope or profile changes) never overwrites them.
        $reason = trim(preg_replace('/^override:?/i', '', (string) ($data['eligibility_reason'] ?? '')));
        $data['eligibility_reason'] = 'override:'.($reason !== '' ? ' '.$reason : '');
        $business->update($data + ['pilot_program_id' => $business->pilot_program_id ?? PilotProgram::active()?->id]);
        $audit->log('pilot.eligibility_override', $business, $data);

        return back()->with('success', __('app.saved'));
    }
}
