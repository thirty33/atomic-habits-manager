<?php

namespace App\Ai\Strategies;

use App\Ai\Contracts\BulkCreatableResource;
use Throwable;

class HabitBulkCreateStrategy implements BulkCreatableResource
{
    public function __construct(private HabitCreateStrategy $single = new HabitCreateStrategy) {}

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
                $result = $this->single->create($userId, $item);
                $results[] = "✓ {$result}";
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
