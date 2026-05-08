<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Application\DTOs;

final readonly class SaveDailyReportEntriesData
{
    /** @param DailyReportEntryData[] $entries */
    public function __construct(public array $entries) {}

    /**
     * @param  array{entries: array<int, array>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entries: array_map(
                fn (array $entry): DailyReportEntryData => DailyReportEntryData::fromArray($entry),
                $data['entries'] ?? [],
            ),
        );
    }
}
