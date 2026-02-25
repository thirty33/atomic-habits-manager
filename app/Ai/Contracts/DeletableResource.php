<?php

namespace App\Ai\Contracts;

interface DeletableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /**
     * @param  array<string, mixed>  $data  Additional options (e.g. schedule_id).
     */
    public function delete(int $userId, int $id, array $data = []): string;
}
