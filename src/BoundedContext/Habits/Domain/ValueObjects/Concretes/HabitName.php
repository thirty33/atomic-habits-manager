<?php

namespace Core\BoundedContext\Habits\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\Primitives\BoundedText;

final class HabitName extends BoundedText
{
    protected function maxLength(): int
    {
        return 255;
    }
}
