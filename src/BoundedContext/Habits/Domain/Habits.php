<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Domain;

use Core\Shared\Domain\Collection;

/**
 * Colección tipada de Habit. Garantiza, en runtime, que solo Habit cabe.
 *
 * El método `map()` está pensado para construir Responses sin exponer la
 * entidad fuera del dominio (ver HabitsResponse en Application).
 *
 * @extends Collection
 */
final class Habits extends Collection
{
    protected function type(): string
    {
        return Habit::class;
    }

    /**
     * @return list<Habit>
     */
    public function items(): array
    {
        /** @var list<Habit> */
        return $this->items;
    }

    /**
     * @template T
     *
     * @param  callable(Habit): T  $fn
     * @return list<T>
     */
    public function map(callable $fn): array
    {
        return array_map($fn, $this->items);
    }
}
