<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\BulkCreatableResource;
use Throwable;

/**
 * Iterates the unitary HabitCreateStrategy. The "single transaction"
 * bulk version is fase-3 work — for now we accept N transactions and
 * keep the partial-success semantic clear in the response.
 */
final class HabitBulkCreateStrategy implements BulkCreatableResource
{
    public function __construct(private readonly HabitCreateStrategy $single) {}

    public function resourceName(): string
    {
        return $this->single->resourceName();
    }

    public function resourceDescription(): string
    {
        return $this->single->resourceDescription();
    }

    public function requiredFields(): array
    {
        return $this->single->requiredFields();
    }

    public function fieldNames(): array
    {
        return $this->single->fieldNames();
    }

    public function bulkCreate(int $userId, array $items): string
    {
        $results = [];
        $success = 0;

        foreach ($items as $item) {
            try {
                $results[] = '✓ '.$this->single->create($userId, $item);
                $success++;
            } catch (Throwable $e) {
                $name = $item['name'] ?? 'desconocido';
                $results[] = "✗ '{$name}': {$e->getMessage()}";
            }
        }

        $total = count($items);

        return "{$success}/{$total} hábitos creados:\n".implode("\n", $results);
    }
}
