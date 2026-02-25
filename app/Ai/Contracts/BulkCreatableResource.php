<?php

namespace App\Ai\Contracts;

interface BulkCreatableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /** @return string[] */
    public function requiredFields(): array;

    /** @return string[] */
    public function fieldNames(): array;

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function bulkCreate(int $userId, array $items): string;
}
