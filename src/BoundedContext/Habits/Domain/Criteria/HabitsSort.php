<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Domain\Criteria;

/**
 * Orden permitido para listar Habits. Whitelist explícita: solo se aceptan
 * los campos listados en ALLOWED_FIELDS. Cualquier otro lanza excepción —
 * así protegemos al adapter de queries arbitrarios desde la capa HTTP.
 */
final readonly class HabitsSort
{
    public const FIELD_NAME = 'name';

    public const FIELD_HABIT_NATURE = 'habit_nature';

    public const FIELD_DESIRE_TYPE = 'desire_type';

    public const FIELD_IS_ACTIVE = 'is_active';

    public const FIELD_CREATED_AT = 'created_at';

    public const FIELD_UPDATED_AT = 'updated_at';

    public const DIRECTION_ASC = 'asc';

    public const DIRECTION_DESC = 'desc';

    private const ALLOWED_FIELDS = [
        self::FIELD_NAME,
        self::FIELD_HABIT_NATURE,
        self::FIELD_DESIRE_TYPE,
        self::FIELD_IS_ACTIVE,
        self::FIELD_CREATED_AT,
        self::FIELD_UPDATED_AT,
    ];

    private const ALLOWED_DIRECTIONS = [self::DIRECTION_ASC, self::DIRECTION_DESC];

    private function __construct(
        public string $field,
        public string $direction,
    ) {}

    public static function by(string $field, string $direction = self::DIRECTION_DESC): self
    {
        if (! in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid sort field "%s". Allowed: %s',
                $field,
                implode(', ', self::ALLOWED_FIELDS)
            ));
        }

        $direction = strtolower($direction);

        if (! in_array($direction, self::ALLOWED_DIRECTIONS, true)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid sort direction "%s". Allowed: %s',
                $direction,
                implode(', ', self::ALLOWED_DIRECTIONS)
            ));
        }

        return new self($field, $direction);
    }

    public static function default(): self
    {
        return new self(self::FIELD_CREATED_AT, self::DIRECTION_DESC);
    }
}
