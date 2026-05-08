<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain\Exceptions;

use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportId;
use DomainException;

final class DailyReportNotFound extends DomainException
{
    public static function withId(DailyReportId $id): self
    {
        return new self(sprintf('DailyReport %d not found.', $id->value()));
    }
}
