<?php

declare(strict_types=1);

namespace Core\BoundedContext\Calendar\Application;

use Core\BoundedContext\Calendar\Application\ReadModels\CalendarBlockSnapshot;
use Core\BoundedContext\Calendar\Domain\ValueObjects\CalendarPeriod;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

interface CalendarReader
{
    /**
     * @return list<CalendarBlockSnapshot>
     */
    public function findBlocksForUserInRange(UserId $userId, CalendarPeriod $period): array;
}
