<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain\ValueObjects;

use Core\Shared\Domain\ValueObjects\Primitives\BoundedText;

final class EntryNotes extends BoundedText
{
    protected function maxLength(): int
    {
        return 2000;
    }
}
