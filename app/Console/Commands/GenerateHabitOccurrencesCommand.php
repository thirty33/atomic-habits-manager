<?php

namespace App\Console\Commands;

use App\Jobs\BatchExtendOccurrencesJob;
use App\Jobs\BatchGenerateOccurrencesJob;
use App\Repositories\HabitRepository;
use App\Repositories\OccurrenceRepository;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class GenerateHabitOccurrencesCommand extends Command
{
    protected $signature = 'habits:generate-occurrences {--chunk=50}';

    protected $description = 'Generate habit occurrences for all users';

    public function __construct(
        private HabitRepository $habitRepository,
        private OccurrenceRepository $occurrenceRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');

        // Step 1: Rebuild — habits with flag = true
        $needsRebuild = $this->habitRepository->getHabitIdsNeedingRebuild();
        $needsRebuild->chunk($chunkSize)->each(fn ($ids) => BatchGenerateOccurrencesJob::dispatch($ids->values()->toArray())
        );
        $this->components->info("Rebuild: {$needsRebuild->count()} habits dispatched.");

        // Step 2: Extend — habits whose last occurrence is < today + 11 months
        $threshold = CarbonImmutable::today()->addMonths(11);
        $needsExtension = $this->habitRepository->getHabitIdsNeedingExtension($threshold);
        $needsExtension->chunk($chunkSize)->each(fn ($ids) => BatchExtendOccurrencesJob::dispatch($ids->values()->toArray())
        );
        $this->components->info("Extend: {$needsExtension->count()} habits dispatched.");

        return self::SUCCESS;
    }
}
