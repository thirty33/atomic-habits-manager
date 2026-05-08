<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Actions;

use Core\BoundedContext\Habits\Application\ReadModels\HabitSnapshot;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

/**
 * Use Case: lista los habits activos del usuario y devuelve snapshots.
 *
 * Snapshots — no entidades — porque los consumidores son cross-BC
 * (calendario, daily reports) y no deben acoplarse a la entidad Domain.
 */
final readonly class FindActiveHabitsForUser
{
    public function __construct(
        private HabitRepository $habits,
    ) {}

    /**
     * @return list<HabitSnapshot>
     */
    public function execute(UserId $userId): array
    {
        $habits = $this->habits->findActiveForUser($userId);

        return $habits->map(fn ($habit) => HabitSnapshot::fromHabit($habit));
    }
}
