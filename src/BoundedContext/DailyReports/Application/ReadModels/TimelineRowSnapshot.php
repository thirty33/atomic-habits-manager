<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\ReadModels;

/**
 * One row of the "today" timeline: an occurrence and its completion status.
 */
final readonly class TimelineRowSnapshot
{
    public function __construct(
        public string $time,
        public string $title,
        public string $detail,
        public string $status,
    ) {}
}
