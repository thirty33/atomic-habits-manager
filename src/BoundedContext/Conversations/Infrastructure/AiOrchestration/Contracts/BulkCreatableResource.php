<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts;

interface BulkCreatableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /**
     * @return list<string>
     */
    public function requiredFields(): array;

    /**
     * @return list<string>
     */
    public function fieldNames(): array;

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function bulkCreate(int $userId, array $items): string;
}
