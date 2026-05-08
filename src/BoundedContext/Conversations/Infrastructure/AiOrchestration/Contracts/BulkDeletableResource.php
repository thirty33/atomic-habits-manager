<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts;

interface BulkDeletableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /**
     * @param  list<int>  $ids
     */
    public function bulkDelete(int $userId, array $ids): string;
}
