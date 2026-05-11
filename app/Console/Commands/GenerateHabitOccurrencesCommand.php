<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\BatchExtendOccurrencesJob;
use App\Jobs\BatchGenerateOccurrencesJob;
use Carbon\CarbonImmutable;
use Core\BoundedContext\Habits\Application\Actions\ListHabitsPendingOccurrenceExtension;
use Core\BoundedContext\Habits\Application\Actions\ListHabitsPendingOccurrenceRebuild;
use Illuminate\Console\Command;

/**
 * Cron diario que mantiene actualizado el horizonte de occurrences:
 *
 *  - Rebuild: encola los habits cuyo flag `needs_occurrence_rebuild` está
 *    activo (porque algún listener cross-BC lo prendió tras un cambio).
 *  - Extend: encola los habits cuyo último `scheduled_date` cae antes del
 *    threshold rolling (11 meses). El job extiende sin regenerar todo.
 *
 * El command vive en Infrastructure y orquesta — chunk + dispatch — pero
 * no toca repositorios directamente. Las queries pasan por Use Cases
 * para mantener la regla "Console → Application → Domain".
 */
final class GenerateHabitOccurrencesCommand extends Command
{
    protected $signature = 'habits:generate-occurrences {--chunk=50}';

    protected $description = 'Generate habit occurrences for all users';

    public function handle(
        ListHabitsPendingOccurrenceRebuild $listRebuild,
        ListHabitsPendingOccurrenceExtension $listExtension,
    ): int {
        $chunkSize = (int) $this->option('chunk');

        $rebuildIds = $listRebuild->execute();
        foreach (array_chunk($rebuildIds, $chunkSize) as $chunk) {
            BatchGenerateOccurrencesJob::dispatch($chunk);
        }
        $this->components->info('Rebuild: '.count($rebuildIds).' habits dispatched.');

        $thresholdYmd = CarbonImmutable::today()->addMonths(11)->toDateString();
        $extendIds = $listExtension->execute($thresholdYmd);
        foreach (array_chunk($extendIds, $chunkSize) as $chunk) {
            BatchExtendOccurrencesJob::dispatch($chunk);
        }
        $this->components->info('Extend: '.count($extendIds).' habits dispatched.');

        return self::SUCCESS;
    }
}
