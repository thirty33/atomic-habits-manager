<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Permission\DTOs;

final readonly class UpdatePermissionData
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int $id, array $data): self
    {
        return new self(
            id: $id,
            name: (string) ($data['name'] ?? ''),
        );
    }
}
