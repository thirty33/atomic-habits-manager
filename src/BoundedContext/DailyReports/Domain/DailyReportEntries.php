<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain;

use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportEntryId;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @implements IteratorAggregate<int, DailyReportEntry>
 */
final class DailyReportEntries implements Countable, IteratorAggregate
{
    /** @param DailyReportEntry[] $entries */
    public function __construct(private array $entries = []) {}

    public static function empty(): self
    {
        return new self([]);
    }

    /** @return DailyReportEntry[] */
    public function all(): array
    {
        return $this->entries;
    }

    public function findById(DailyReportEntryId $id): ?DailyReportEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->id() !== null && $entry->id()->equals($id)) {
                return $entry;
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function getIterator(): Traversable
    {
        foreach ($this->entries as $entry) {
            yield $entry;
        }
    }
}
