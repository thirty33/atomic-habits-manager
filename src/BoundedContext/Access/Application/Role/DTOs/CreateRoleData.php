<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Role\DTOs;

final readonly class CreateRoleData
{
    /**
     * @param  list<int>  $permissionIds
     */
    public function __construct(
        public string $name,
        public array $permissionIds,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            permissionIds: array_values(array_map('intval', (array) ($data['permissions'] ?? []))),
        );
    }
}
