<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Role\DTOs;

final readonly class UpdateRoleData
{
    /**
     * @param  list<int>  $permissionIds
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $permissionIds,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int $id, array $data): self
    {
        return new self(
            id: $id,
            name: (string) ($data['name'] ?? ''),
            permissionIds: array_values(array_map('intval', (array) ($data['permissions'] ?? []))),
        );
    }
}
