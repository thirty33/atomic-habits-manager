<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain\Exceptions;

use Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes\HabitScheduleId;
use Core\Shared\Domain\DomainException;

/**
 * Domain exception: an operation was attempted on a HabitSchedule that does
 * not exist (or that does not belong to the requesting user).
 *
 * Application throws it; Infrastructure (bootstrap/app.php) translates it
 * into a 404 HTTP response. The domain never knows about HTTP codes.
 */
final class HabitScheduleNotFound extends DomainException
{
    public static function withId(HabitScheduleId $id): self
    {
        return new self(sprintf('HabitSchedule with id %d not found.', $id->value()));
    }
}
