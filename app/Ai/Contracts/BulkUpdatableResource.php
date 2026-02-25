<?php

namespace App\Ai\Contracts;

interface BulkUpdatableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /** @return string[] */
    public function updatableFields(): array;

    /** @return string[] */
    public function fieldNames(): array;

    /**
     * @param  array<int, array{id: int, ...}>  $items  cada item debe incluir 'id'
     */
    public function bulkUpdate(int $userId, array $items): string;
}
