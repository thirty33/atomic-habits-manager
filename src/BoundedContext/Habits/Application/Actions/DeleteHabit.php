<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Actions;

use Core\BoundedContext\Habits\Domain\Exceptions\HabitNotFound;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

final readonly class DeleteHabit
{
    public function __construct(private HabitRepository $repository) {}

    public function __invoke(int $habitId, int $userId): void
    {
        $id = HabitId::from($habitId);
        $owner = UserId::from($userId);

        $habit = $this->repository->findForUser($id, $owner);

        if ($habit === null) {
            throw HabitNotFound::withId($id);
        }

        $this->repository->delete($habit);
    }
}
