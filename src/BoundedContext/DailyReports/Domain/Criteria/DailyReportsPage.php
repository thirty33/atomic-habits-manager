<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Domain\Criteria;

use Core\BoundedContext\DailyReports\Domain\DailyReports;

/**
 * Resultado paginado de aplicar DailyReportsCriteria sobre el repositorio.
 *
 * Es el equivalente puro de Laravel\LengthAwarePaginator, pero sin
 * dependencia de Illuminate. Application puede emitirlo sin saber nada
 * del paginator de Laravel.
 */
final readonly class DailyReportsPage
{
    public function __construct(
        public DailyReports $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
        if ($total < 0) {
            throw new \InvalidArgumentException('total must be >= 0');
        }

        if ($page < 1) {
            throw new \InvalidArgumentException('page must be >= 1');
        }

        if ($perPage < 1) {
            throw new \InvalidArgumentException('perPage must be >= 1');
        }
    }

    public function lastPage(): int
    {
        if ($this->total === 0) {
            return 1;
        }

        return (int) ceil($this->total / $this->perPage);
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->lastPage();
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    public function from(): int
    {
        if ($this->items->isEmpty()) {
            return 0;
        }

        return ($this->page - 1) * $this->perPage + 1;
    }

    public function to(): int
    {
        if ($this->items->isEmpty()) {
            return 0;
        }

        return $this->from() + $this->items->count() - 1;
    }
}
