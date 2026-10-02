<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Analytics\DashboardReport;
use App\Domain\Analytics\KpiService;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(DashboardReport $report, KpiService $kpis): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'summary' => $report->summary(),
            'kpis' => $kpis->evaluate(),
            'charts' => [
                'byCategory' => $report->casesByCategory(),
                'byRegion' => $report->casesByRegion(),
                'byIndustry' => $report->casesByIndustry(),
                'aiVsHuman' => $report->aiVsHuman(),
                'funnel' => $report->funnel(),
                'overTime' => $report->casesOverTime(),
            ],
            'experts' => $report->expertPerformance(),
        ]);
    }
}
