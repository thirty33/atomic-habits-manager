<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Domain;

use ArrayIterator;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Countable;
use IteratorAggregate;
use Traversable;

final class HabitOccurrences implements Countable, IteratorAggregate
{
    /** @var list<HabitOccurrence> */
    private array $items;

    /**
     * @param  list<HabitOccurrence>  $items
     */
    public function __construct(array $items)
    {
        $this->items = array_values($items);
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @return list<HabitOccurrence>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function futureOf(OccurrenceDate $threshold): self
    {
        return new self(array_values(array_filter(
            $this->items,
            static fn (HabitOccurrence $o) => $o->scheduledDate()->isAfter($threshold)
                || $o->scheduledDate()->equals($threshold),
        )));
    }

    public function lastDate(): ?OccurrenceDate
    {
        if ($this->isEmpty()) {
            return null;
        }
        $latest = $this->items[0]->scheduledDate();
        foreach ($this->items as $occurrence) {
            if ($occurrence->scheduledDate()->isAfter($latest)) {
                $latest = $occurrence->scheduledDate();
            }
        }

        return $latest;
    }
}
