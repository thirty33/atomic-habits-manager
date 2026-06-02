<?php

namespace Core\BoundedContext\Habits\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\Primitives\BoundedText;

final class HabitDescription extends BoundedText
{
    protected function maxLength(): int
    {
        return 2000;
    }
}
