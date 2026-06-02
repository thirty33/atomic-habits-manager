<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Actions;

use Core\BoundedContext\Habits\Application\Responses\HabitsResponse;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * Versión no paginada — para CLI, jobs o exports. Si la lista crece a
 * miles de registros, conviene migrar el caller a ListHabits con paginación
 * o agregar un método streaming al repositorio.
 */
final readonly class ListAllHabits
{
    public function __construct(private HabitRepository $repository) {}

    public function __invoke(int $userId): HabitsResponse
    {
        $habits = $this->repository->findAllForUser(UserId::from($userId));

        return HabitsResponse::fromHabits($habits);
    }
}
