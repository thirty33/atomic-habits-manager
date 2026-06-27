<?php

declare(strict_types=1);

namespace Core\BoundedContext\Access\Application\Module\DTOs;

final readonly class CreateModuleData
{
    public function __construct(
        public string $code,
        public string $name,
        public bool $isActive = true,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            code: (string) ($data['code'] ?? ''),
            name: (string) ($data['name'] ?? ''),
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
