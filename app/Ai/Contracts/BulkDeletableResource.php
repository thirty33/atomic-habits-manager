<?php

namespace App\Ai\Contracts;

interface BulkDeletableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /**
     * @param  int[]  $ids
     */
    public function bulkDelete(int $userId, array $ids): string;
}
