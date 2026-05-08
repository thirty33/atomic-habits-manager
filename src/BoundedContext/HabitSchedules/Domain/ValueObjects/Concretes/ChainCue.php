<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes;

use Core\BoundedContext\Habits\Domain\ValueObjects\Primitives\BoundedText;

final class ChainCue extends BoundedText
{
    public const MAX_LENGTH = 500;

    protected function maxLength(): int
    {
        return self::MAX_LENGTH;
    }
}
