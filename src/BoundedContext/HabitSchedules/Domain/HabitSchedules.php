<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain;

use Core\Shared\Domain\Collection;

/**
 * Typed collection of HabitSchedule. Guarantees, at runtime, that only
 * HabitSchedule instances are accepted.
 *
 * @extends Collection
 */
final class HabitSchedules extends Collection
{
    protected function type(): string
    {
        return HabitSchedule::class;
    }

    /**
     * @return list<HabitSchedule>
     */
    public function items(): array
    {
        /** @var list<HabitSchedule> */
        return $this->items;
    }

    /**
     * @template T
     *
     * @param  callable(HabitSchedule): T  $fn
     * @return list<T>
     */
    public function map(callable $fn): array
    {
        return array_map($fn, $this->items);
    }
}
