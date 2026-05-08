<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain;

use Core\Shared\Domain\Collection;

/**
 * @extends Collection<DailyReport>
 */
final class DailyReports extends Collection
{
    protected function type(): string
    {
        return DailyReport::class;
    }

    /**
     * @return list<DailyReport>
     */
    public function items(): array
    {
        /** @var list<DailyReport> */
        return $this->items;
    }
}
