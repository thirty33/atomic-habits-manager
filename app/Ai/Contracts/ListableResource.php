<?php

namespace App\Ai\Contracts;

interface ListableResource
{
    /**
     * Resource identifier for the tool schema.
     */
    public function resourceName(): string;

    /**
     * Description so the AI understands what this resource contains.
     */
    public function resourceDescription(): string;

    /**
     * Run the query and return formatted text for the AI.
     *
     * @param  int  $userId  Authenticated user ID
     * @param  int|null  $parentId  Parent resource ID (for child resources)
     */
    public function list(int $userId, ?int $parentId = null): string;
}
