<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain\ValueObjects;

use Core\BoundedContext\Habits\Domain\ValueObjects\Primitives\BoundedText;

final class ReportNotes extends BoundedText
{
    protected function maxLength(): int
    {
        return 5000;
    }
}
