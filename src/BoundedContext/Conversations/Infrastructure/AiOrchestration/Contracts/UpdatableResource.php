<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts;

use Illuminate\Contracts\JsonSchema\JsonSchema;

interface UpdatableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /**
     * @return list<string>
     */
    public function updatableFields(): array;

    /**
     * @return list<string>
     */
    public function fieldNames(): array;

    /**
     * @return array<string, mixed>
     */
    public function schemaFields(JsonSchema $schema): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $userId, int $id, array $data): string;
}
