<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitOccurrences\Domain\Exceptions;

use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\HabitOccurrenceId;
use DomainException;

final class HabitOccurrenceNotFound extends DomainException
{
    public static function withId(HabitOccurrenceId $id): self
    {
        return new self("HabitOccurrence not found: {$id->value()}");
    }
}
