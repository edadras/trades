<?php

use App\Jobs\SendScheduledReminders;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new SendScheduledReminders)->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('kpi:snapshot')->dailyAt('23:55');
Schedule::command('platform:backup')->dailyAt('02:30')->onOneServer();
Schedule::command('platform:prune-data')->weeklyOn(0, '03:30')->onOneServer();
Schedule::command('horizon:snapshot')->everyFiveMinutes();
Schedule::command('auth:clear-resets')->daily();
