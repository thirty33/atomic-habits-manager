<?php

namespace Core\BoundedContext\Habits\Domain\ValueObjects\Concretes;

use Core\BoundedContext\Habits\Domain\ValueObjects\Primitives\StringEnum;

final class HabitNature extends StringEnum
{
    public const BUILD = 'build';

    public const BREAK = 'break';

    protected function allowedValues(): array
    {
        return [self::BUILD, self::BREAK];
    }

    public function label(): string
    {
        return match ($this->value) {
            self::BUILD => 'Quiero adoptar un buen hábito',
            self::BREAK => 'Quiero dejar un mal hábito',
        };
    }

    public function color(): string
    {
        return match ($this->value) {
            self::BUILD => '#22C55E',
            self::BREAK => '#EF4444',
        };
    }

    public function isBuild(): bool
    {
        return $this->value === self::BUILD;
    }

    public function isBreak(): bool
    {
        return $this->value === self::BREAK;
    }
}
