<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Permission\DTOs;

final readonly class CreatePermissionData
{
    public function __construct(
        public string $name,
        public int $moduleId,
        public string $code,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            moduleId: (int) ($data['module_id'] ?? 0),
            code: (string) ($data['code'] ?? ''),
        );
    }
}
