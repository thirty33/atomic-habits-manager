<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\DTOs;

/**
 * Datos primitivos para actualizar un Habit existente.
 *
 * - habitId: identifica el Habit objetivo.
 * - userId: dueño esperado. Si no coincide con el del Habit, el Use Case
 *   lanza HabitNotFound (ocultamos la existencia del recurso ajeno).
 * - isActive: opcional; si se envía, dispara activate()/deactivate().
 */
final readonly class UpdateHabitData
{
    public function __construct(
        public int $habitId,
        public int $userId,
        public string $name,
        public string $habitNature,
        public string $desireType,
        public ?bool $isActive = null,
        public ?string $description = null,
        public ?string $color = null,
        public ?string $implementationIntention = null,
        public ?string $location = null,
        public ?string $cue = null,
        public ?string $reframe = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            habitId: (int) $data['habit_id'],
            userId: (int) $data['user_id'],
            name: (string) $data['name'],
            habitNature: (string) $data['habit_nature'],
            desireType: (string) $data['desire_type'],
            isActive: self::nullableBool($data, 'is_active'),
            description: self::nullableString($data, 'description'),
            color: self::nullableString($data, 'color'),
            implementationIntention: self::nullableString($data, 'implementation_intention'),
            location: self::nullableString($data, 'location'),
            cue: self::nullableString($data, 'cue'),
            reframe: self::nullableString($data, 'reframe'),
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

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function nullableBool(array $data, string $key): ?bool
    {
        if (! array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if ($value === null) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }
}
