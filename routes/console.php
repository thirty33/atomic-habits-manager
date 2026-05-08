<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Relay corre cada 10s para minimizar la latencia percibida en el chat IA.
// Cada conversación atraviesa 3 hops por outbox (UserMessageWasPosted →
// AssistantMessageWasPosted → AssistantMessageWasApproved); con tick
// everyMinute el peor caso era ~3 minutos. Con everyTenSeconds queda en
// ~30s. Si el throughput crece, considerar Pattern 3 "full" (despachar
// el bucket Job dentro de la misma transacción que escribe al outbox y
// dejar el relay solo como retry safety-net).
Schedule::command('events:relay --once --limit=200')->everyTenSeconds()->withoutOverlapping()->runInBackground();
// `atomic-ia:process` (cron safety net for the AI reply path) is
// deprecated in flow 05. The outbox + relay + heavy bucket already
// drain pending user messages. The command stays in disk
// (app/Console/Commands/ProcessPendingMessagesCommand.php) for rapid
// rollback during the verification window — re-enable here if outbox
// regressions appear in production.
// `atomic-ia:moderate` cron safety-net is disabled while the new
// HandleAiResponseAction pipeline runs moderation synchronously inside
// the same transaction. Re-enable here if pipeline regressions appear in
// production. The command itself stays on disk for rollback.
// Schedule::command('atomic-ia:moderate')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('habits:generate-occurrences')->dailyAt('03:00')->withoutOverlapping();
