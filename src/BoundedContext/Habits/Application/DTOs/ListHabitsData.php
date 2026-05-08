<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\DTOs;

/**
 * Filtros + paginación para listar Habits, todavía en primitivas. El Use
 * Case ListHabits los traduce a HabitsCriteria (que sí valida dominio).
 *
 * sortField / sortDirection son strings — la validación contra la
 * whitelist la hace HabitsSort en Domain.
 */
final readonly class ListHabitsData
{
    public function __construct(
        public int $userId,
        public ?string $search = null,
        public ?string $habitNature = null,
        public ?string $desireType = null,
        public ?bool $isActive = null,
        public string $sortField = 'created_at',
        public string $sortDirection = 'desc',
        public int $page = 1,
        public int $perPage = 10,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            search: self::nullableString($data, 'query') ?? self::nullableString($data, 'search'),
            habitNature: self::nullableString($data, 'habit_nature'),
            desireType: self::nullableString($data, 'desire_type'),
            isActive: self::nullableBool($data, 'is_active'),
            sortField: (string) ($data['sort_field'] ?? 'created_at'),
            sortDirection: (string) ($data['sort_direction'] ?? 'desc'),
            page: max(1, (int) ($data['page'] ?? 1)),
            perPage: max(1, (int) ($data['per_page'] ?? 10)),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function nullableString(array $data, string $key): ?string
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function nullableBool(array $data, string $key): ?bool
    {
        if (! array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            return null;
        }

        return filter_var($data[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
}
