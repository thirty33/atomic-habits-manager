<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain\ValueObjects;

use Core\Shared\Domain\ValueObjects\Primitives\StringEnum;

final class Mood extends StringEnum
{
    public const GREAT = 'great';

    public const GOOD = 'good';

    public const NEUTRAL = 'neutral';

    public const BAD = 'bad';

    public const TERRIBLE = 'terrible';

    protected function allowedValues(): array
    {
        return [
            self::GREAT,
            self::GOOD,
            self::NEUTRAL,
            self::BAD,
            self::TERRIBLE,
        ];
    }
}
