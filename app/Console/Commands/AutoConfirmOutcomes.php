<?php

namespace App\Console\Commands;

use App\Domain\Cases\Actions\ConfirmOutcome;
use App\Domain\Cases\Models\CaseOutcome;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('outcomes:auto-confirm')]
#[Description('Confirm proposed outcomes the business has not answered within the confirmation window')]
class AutoConfirmOutcomes extends Command
{
    public function handle(ConfirmOutcome $confirm): int
    {
        $count = 0;
        CaseOutcome::query()->where('confirmation_status', 'pending')->whereNull('superseded_at')
            ->where('created_at', '<', now()->subDays(config('platform.outcome_confirmation_days')))
            ->each(function (CaseOutcome $outcome) use ($confirm, &$count) {
                $confirm->confirm($outcome, null, 'outcome_auto_confirmed');
                $count++;
            });
        $this->info("Auto-confirmed: {$count}");

        return self::SUCCESS;
    }
}
