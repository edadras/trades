<?php

namespace App\Console\Commands;

use App\Domain\Analytics\KpiService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('kpi:snapshot')]
#[Description('Store today\'s value of every active KPI')]
class SnapshotKpis extends Command
{
    public function handle(KpiService $kpis): int
    {
        $this->info('Snapshots stored: '.$kpis->snapshot());

        return self::SUCCESS;
    }
}
