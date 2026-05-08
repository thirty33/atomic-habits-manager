<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\BulkDeletableResource;
use Throwable;

final class HabitBulkDeleteStrategy implements BulkDeletableResource
{
    public function __construct(private readonly HabitDeleteStrategy $single) {}

    public function resourceName(): string
    {
        return $this->single->resourceName();
    }

    public function resourceDescription(): string
    {
        return $this->single->resourceDescription();
    }

    public function bulkDelete(int $userId, array $ids): string
    {
        $results = [];
        $success = 0;

        foreach ($ids as $id) {
            try {
                $results[] = '✓ '.$this->single->delete($userId, $id);
                $success++;
            } catch (Throwable $e) {
                $results[] = "✗ ID {$id}: {$e->getMessage()}";
            }
        }

        $total = count($ids);

        return "{$success}/{$total} hábitos eliminados:\n".implode("\n", $results);
    }
}
