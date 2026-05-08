<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('events:relay --once --limit=200')->everyMinute()->withoutOverlapping()->runInBackground();
// `atomic-ia:process` (cron safety net for the AI reply path) is
// deprecated in flow 05. The outbox + relay + heavy bucket already
// drain pending user messages. The command stays in disk
// (app/Console/Commands/ProcessPendingMessagesCommand.php) for rapid
// rollback during the verification window — re-enable here if outbox
// regressions appear in production.
Schedule::command('atomic-ia:moderate')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('habits:generate-occurrences')->dailyAt('03:00')->withoutOverlapping();
