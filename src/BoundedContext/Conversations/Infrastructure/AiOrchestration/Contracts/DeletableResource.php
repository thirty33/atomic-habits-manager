<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts;

interface DeletableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /**
     * @param  array<string, mixed>  $data  Additional options (e.g. schedule_id).
     */
    public function delete(int $userId, int $id, array $data = []): string;
}
