<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\BulkUpdatableResource;
use Throwable;

final class HabitBulkUpdateStrategy implements BulkUpdatableResource
{
    public function __construct(private readonly HabitUpdateStrategy $single) {}

    public function resourceName(): string
    {
        return $this->single->resourceName();
    }

    public function resourceDescription(): string
    {
        return $this->single->resourceDescription();
    }

    public function updatableFields(): array
    {
        return $this->single->updatableFields();
    }

    public function fieldNames(): array
    {
        return $this->single->fieldNames();
    }

    public function bulkUpdate(int $userId, array $items): string
    {
        $results = [];
        $success = 0;

        foreach ($items as $item) {
            $id = (int) ($item['id'] ?? 0);
            $data = $item;
            unset($data['id']);

            try {
                $this->single->update($userId, $id, $data);
                $results[] = "✓ ID {$id}";
                $success++;
            } catch (Throwable $e) {
                $results[] = "✗ ID {$id}: {$e->getMessage()}";
            }
        }

        $total = count($items);

        return "{$success}/{$total} hábitos actualizados:\n".implode("\n", $results);
    }
}
