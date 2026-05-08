<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts;

interface ListableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    public function list(int $userId, ?int $parentId = null): string;
}
