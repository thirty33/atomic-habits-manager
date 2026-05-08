<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\Actions;

use Core\BoundedContext\HabitOccurrences\Application\ReadModels\HabitOccurrenceSnapshot;
use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrenceRepository;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

final readonly class GetOccurrencesForDate
{
    public function __construct(
        private HabitOccurrenceRepository $repository,
    ) {}

    /**
     * @return list<HabitOccurrenceSnapshot>
     */
    public function __invoke(UserId $userId, OccurrenceDate $date): array
    {
        return $this->repository->findForUserOnDate($userId, $date);
    }
}
