<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Application\Actions;

use Core\BoundedContext\HabitOccurrences\Application\HabitOccurrenceReader;
use Core\BoundedContext\HabitOccurrences\Application\ReadModels\HabitOccurrenceSnapshot;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

final readonly class GetOccurrencesInRange
{
    public function __construct(
        private HabitOccurrenceReader $reader,
    ) {}

    /**
     * @return list<HabitOccurrenceSnapshot>
     */
    public function __invoke(UserId $userId, OccurrenceDate $from, OccurrenceDate $to): array
    {
        return $this->reader->findForUserInRange($userId, $from, $to);
    }
}
