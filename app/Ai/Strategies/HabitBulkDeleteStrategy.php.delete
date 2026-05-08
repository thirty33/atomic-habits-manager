<?php

namespace App\Ai\Strategies;

use App\Ai\Contracts\BulkDeletableResource;
use Throwable;

class HabitBulkDeleteStrategy implements BulkDeletableResource
{
    public function __construct(private HabitDeleteStrategy $single = new HabitDeleteStrategy) {}

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
                $result = $this->single->delete($userId, $id);
                $results[] = "✓ {$result}";
                $success++;
            } catch (Throwable $e) {
                $results[] = "✗ ID {$id}: {$e->getMessage()}";
            }
        }

        $total = count($ids);

        return "{$success}/{$total} hábitos eliminados:\n".implode("\n", $results);
    }
}
