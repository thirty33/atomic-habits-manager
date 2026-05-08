<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts;

interface BulkUpdatableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /**
     * @return list<string>
     */
    public function updatableFields(): array;

    /**
     * @return list<string>
     */
    public function fieldNames(): array;

    /**
     * @param  list<array{id: int}|array<string, mixed>>  $items
     */
    public function bulkUpdate(int $userId, array $items): string;
}
