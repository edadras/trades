<?php

namespace App\Console\Commands;

use App\Domain\Pilot\Actions\GeneratePilotReport;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('pilot:weekly-report')]
#[Description('Generate last week\'s pilot report of results and errors and notify the pilot team')]
class GenerateWeeklyReport extends Command
{
    public function handle(GeneratePilotReport $action): int
    {
        $report = $action->handle();
        $this->info("Report for week starting {$report->week_start->toDateString()} generated.");

        return self::SUCCESS;
    }
}
