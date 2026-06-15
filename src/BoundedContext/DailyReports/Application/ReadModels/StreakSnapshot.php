<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\ReadModels;

/**
 * Current completion streak plus the per-day grid used to render it.
 */
final readonly class StreakSnapshot
{
    /**
     * @param  list<int>  $cells  One entry per day (1 = fully completed, 0 = not).
     */
    public function __construct(
        public int $count,
        public array $cells,
        public string $fromDate,
        public string $toDate,
        public bool $isRecord,
    ) {}
}
