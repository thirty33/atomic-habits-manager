<?php

namespace Core\BoundedContext\Habits\Domain\ValueObjects\Concretes;

use Core\BoundedContext\Habits\Domain\ValueObjects\Primitives\BoundedText;

final class HabitCue extends BoundedText
{
    protected function maxLength(): int
    {
        return 255;
    }
}
