<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Responses;

use Core\BoundedContext\Habits\Domain\Habits;

/**
 * Lista plana (no paginada) de HabitResponse. Pensada para CLI / exports.
 */
final readonly class HabitsResponse
{
    /**
     * @param  list<HabitResponse>  $items
     */
    public function __construct(public array $items) {}

    public static function fromHabits(Habits $habits): self
    {
        return new self($habits->map(static fn ($habit) => HabitResponse::fromHabit($habit)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(static fn (HabitResponse $r) => $r->toArray(), $this->items);
    }
}
