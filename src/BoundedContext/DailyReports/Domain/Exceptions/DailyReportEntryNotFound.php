<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain\Exceptions;

use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportEntryId;
use DomainException;

final class DailyReportEntryNotFound extends DomainException
{
    public static function withId(DailyReportEntryId $id): self
    {
        return new self(sprintf('DailyReportEntry %d not found.', $id->value()));
    }
}
