<?php

declare(strict_types=1);

namespace Core\BoundedContext\Calendar\Application\Actions;

use Core\BoundedContext\Calendar\Application\CalendarReader;
use Core\BoundedContext\Calendar\Domain\ValueObjects\CalendarPeriod;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

final readonly class GetCalendarBlocksInRange
{
    public function __construct(private CalendarReader $reader) {}

    /**
     * @return list<\Core\BoundedContext\Calendar\Application\ReadModels\CalendarBlockSnapshot>
     */
    public function __invoke(UserId $userId, CalendarPeriod $period): array
    {
        return $this->reader->findBlocksForUserInRange($userId, $period);
    }
}
