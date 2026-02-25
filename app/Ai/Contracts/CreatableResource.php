<?php

namespace App\Ai\Contracts;

use Illuminate\Contracts\JsonSchema\JsonSchema;

interface CreatableResource
{
    public function resourceName(): string;

    public function resourceDescription(): string;

    /**
     * Required field names, used in the tool description.
     *
     * @return string[]
     */
    public function requiredFields(): array;

    /**
     * All possible field names (required + optional).
     * Used by CreateResourceTool to extract data from the AI request.
     *
     * @return string[]
     */
    public function fieldNames(): array;

    /**
     * JSON Schema field definitions.
     * Used by CreateResourceTool to build its schema dynamically.
     */
    public function schemaFields(JsonSchema $schema): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(int $userId, array $data): string;
}
