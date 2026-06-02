<?php

namespace Core\BoundedContext\Habits\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\Primitives\StringEnum;

final class DesireType extends StringEnum
{
    public const NEED = 'need';

    public const WANT = 'want';

    public const NEUTRAL = 'neutral';

    protected function allowedValues(): array
    {
        return [self::NEED, self::WANT, self::NEUTRAL];
    }

    public function label(): string
    {
        return match ($this->value) {
            self::NEED => 'Es algo que necesito hacer',
            self::WANT => 'Es algo que quiero hacer',
            self::NEUTRAL => 'No estoy seguro aún',
        };
    }

    public function isNeed(): bool
    {
        return $this->value === self::NEED;
    }

    public function isWant(): bool
    {
        return $this->value === self::WANT;
    }

    public function isNeutral(): bool
    {
        return $this->value === self::NEUTRAL;
    }
}
