<?php

namespace App\Ai\Contracts;

use Illuminate\Contracts\JsonSchema\JsonSchema;

interface UpdatableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /**
     * Updatable field names, used in the tool description.
     *
     * @return string[]
     */
    public function updatableFields(): array;

    /**
     * All possible field names the tool may receive from the AI.
     * Used by UpdateResourceTool to extract data from the AI request.
     *
     * @return string[]
     */
    public function fieldNames(): array;

    /**
     * JSON Schema field definitions.
     * Used by UpdateResourceTool to build its schema dynamically.
     */
    public function schemaFields(JsonSchema $schema): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $userId, int $id, array $data): string;
}
