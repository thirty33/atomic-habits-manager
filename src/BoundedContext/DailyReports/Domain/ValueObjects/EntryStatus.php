<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain\ValueObjects;

use Core\BoundedContext\Habits\Domain\ValueObjects\Primitives\StringEnum;

final class EntryStatus extends StringEnum
{
    public const PENDING = 'pending';

    public const COMPLETED = 'completed';

    public const PARTIAL = 'partial';

    public const NOT_COMPLETED = 'not_completed';

    public const SKIPPED = 'skipped';

    protected function allowedValues(): array
    {
        return [
            self::PENDING,
            self::COMPLETED,
            self::PARTIAL,
            self::NOT_COMPLETED,
            self::SKIPPED,
        ];
    }

    public function isCompletedLike(): bool
    {
        return in_array($this->value(), [self::COMPLETED, self::PARTIAL], true);
    }
}
