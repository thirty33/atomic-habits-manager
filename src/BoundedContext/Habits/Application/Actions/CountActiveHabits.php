<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Actions;

use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * Number of active (is_active = true, not soft-deleted) habits for a user.
 */
final readonly class CountActiveHabits
{
    public function __construct(private HabitRepository $habits) {}

    public function __invoke(UserId $userId): int
    {
        return $this->habits->findActiveForUser($userId)->count();
    }
}
