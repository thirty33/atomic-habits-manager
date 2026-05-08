<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts;

use Illuminate\Contracts\JsonSchema\JsonSchema;

interface CreatableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /**
     * @return list<string>
     */
    public function requiredFields(): array;

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
    public function create(int $userId, array $data): string;
}
