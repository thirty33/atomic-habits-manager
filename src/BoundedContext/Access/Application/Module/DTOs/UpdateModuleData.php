<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Module\DTOs;

final readonly class UpdateModuleData
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isActive,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int $id, array $data): self
    {
        return new self(
            id: $id,
            name: (string) ($data['name'] ?? ''),
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
