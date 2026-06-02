<?php

declare(strict_types=1);

namespace Core\BoundedContext\HabitSchedules\Domain\ValueObjects\Concretes;

use Core\Shared\Domain\ValueObjects\Primitives\StringEnum;

final class RecurrenceType extends StringEnum
{
    public const NONE = 'none';

    public const DAILY = 'daily';

    public const WEEKLY = 'weekly';

    public const EVERY_N_DAYS = 'every_n_days';

    protected function allowedValues(): array
    {
        return [self::NONE, self::DAILY, self::WEEKLY, self::EVERY_N_DAYS];
    }

    public function isNone(): bool
    {
        return $this->value === self::NONE;
    }

    public function isDaily(): bool
    {
        return $this->value === self::DAILY;
    }

    public function isWeekly(): bool
    {
        return $this->value === self::WEEKLY;
    }

    public function isEveryNDays(): bool
    {
        return $this->value === self::EVERY_N_DAYS;
    }
}
