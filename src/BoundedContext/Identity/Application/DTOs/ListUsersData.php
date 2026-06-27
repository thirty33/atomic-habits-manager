<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\DTOs;

final readonly class ListUsersData
{
    public function __construct(
        public ?string $search,
        public ?bool $isActive,
        public string $sortField,
        public string $sortDirection,
        public int $page,
        public int $perPage,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $isActive = $data['is_active'] ?? null;

        return new self(
            search: isset($data['query']) && $data['query'] !== '' ? (string) $data['query'] : null,
            isActive: $isActive === null || $isActive === '' ? null : filter_var($isActive, FILTER_VALIDATE_BOOLEAN),
            sortField: (string) ($data['sort_field'] ?? 'created_at'),
            sortDirection: (string) ($data['sort_direction'] ?? 'desc'),
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
        );
    }
}
