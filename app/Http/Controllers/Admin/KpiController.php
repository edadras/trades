<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\KpiService;
use App\Domain\Analytics\MetricCalculator;
use App\Domain\Analytics\Models\Kpi;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/** KPIs are configurable data: admins define KPIs, bind them to a metric and set dated targets. */
class KpiController extends Controller
{
    public function index(KpiService $service): Response
    {
        return Inertia::render('Admin/Kpis', [
            'kpis' => Kpi::with('targets')->orderBy('sort_order')->get()->map(fn (Kpi $k) => [
                'id' => $k->id, 'key' => $k->key, 'name' => $k->translations('name'), 'description' => $k->translations('description'),
                'metric' => $k->metric, 'unit' => $k->unit, 'comparator' => $k->comparator, 'is_active' => $k->is_active, 'sort_order' => $k->sort_order,
                'targets' => $k->targets->map(fn ($t) => ['id' => $t->id, 'target_value' => $t->target_value, 'period_start' => $t->period_start?->toDateString(), 'period_end' => $t->period_end?->toDateString()])->all(),
            ]),
            'live' => $service->evaluate(),
            'metrics' => MetricCalculator::metrics(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $kpi = Kpi::create($this->validated($request));
        $this->storeTarget($request, $kpi);

        return back()->with('success', __('app.saved'));
    }

    public function update(Request $request, Kpi $kpi): RedirectResponse
    {
        $kpi->update($this->validated($request, $kpi));
        $this->storeTarget($request, $kpi);

        return back()->with('success', __('app.saved'));
    }

    public function snapshot(KpiService $service): RedirectResponse
    {
        $service->snapshot();

        return back()->with('success', __('app.saved'));
    }

    private function validated(Request $request, ?Kpi $kpi = null): array
    {
        return $request->validate([
            'key' => ['required', 'alpha_dash', 'max:60', Rule::unique('kpis', 'key')->ignore($kpi?->id)],
            'name.fa' => ['required', 'string', 'max:150'], 'name.en' => ['required', 'string', 'max:150'],
            'description.fa' => ['nullable', 'string', 'max:500'], 'description.en' => ['nullable', 'string', 'max:500'],
            'metric' => ['required', Rule::in(array_keys(MetricCalculator::metrics()))],
            'unit' => ['required', 'in:count,percent,hours,score'],
            'comparator' => ['required', 'in:>=,<='],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);
    }

    private function storeTarget(Request $request, Kpi $kpi): void
    {
        $data = $request->validate([
            'target_value' => ['nullable', 'numeric'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
        ]);
        if (isset($data['target_value'])) {
            $current = $kpi->currentTarget()->first();
            if (! $current || (float) $current->target_value !== (float) $data['target_value'] || $data['period_start'] || $data['period_end']) {
                $kpi->targets()->create($data + ['created_by' => $request->user()->id]);
            }
        }
    }
}
