<?php

namespace App\Domain\Analytics;

use App\Domain\Analytics\Models\Kpi;
use App\Domain\Analytics\Models\KpiSnapshot;
use Illuminate\Support\Collection;

class KpiService
{
    public function __construct(private readonly MetricCalculator $metrics) {}

    /** Live evaluation of all active KPIs against their current targets. */
    public function evaluate(): Collection
    {
        return Kpi::query()->where('is_active', true)->with('currentTarget')->orderBy('sort_order')->get()
            ->map(function (Kpi $kpi) {
                $value = $this->metrics->value($kpi->metric);
                $target = $kpi->currentTarget?->target_value;

                return [
                    'id' => $kpi->id,
                    'key' => $kpi->key,
                    'name' => $kpi->translate('name'),
                    'description' => $kpi->translate('description'),
                    'metric' => $kpi->metric,
                    'unit' => $kpi->unit,
                    'comparator' => $kpi->comparator,
                    'value' => $value,
                    'target' => $target,
                    'progress' => $this->progress($kpi, $value, $target),
                    'achieved' => $kpi->isAchieved($value, $target),
                    'trend' => $kpi->snapshots()->latest('captured_on')->limit(14)->get()->reverse()->pluck('value')->values()->all(),
                ];
            });
    }

    /** 0..100 progress towards the target; for "<=" targets, meeting the limit counts as 100. */
    public function progress(Kpi $kpi, ?float $value, ?float $target): ?int
    {
        if ($value === null || ! $target) {
            return null;
        }
        if ($kpi->comparator === '<=') {
            return (int) round(min(100, $value <= $target ? 100 : $target / $value * 100));
        }

        return (int) round(min(100, $value / $target * 100));
    }

    public function snapshot(): int
    {
        $count = 0;
        foreach ($this->evaluate() as $row) {
            if ($row['value'] === null) {
                continue;
            }
            KpiSnapshot::updateOrCreate(['kpi_id' => $row['id'], 'captured_on' => now()->toDateString()], [
                'value' => $row['value'], 'target_value' => $row['target'], 'achieved' => $row['achieved'],
            ]);
            $count++;
        }

        return $count;
    }
}
