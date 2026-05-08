<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\DTOs;

/**
 * Datos primitivos para crear un Habit. Solo strings/ints/bools — la
 * validación de dominio (longitudes, enums, hex color) la hacen los VOs
 * cuando el Use Case los construye.
 *
 * El controller arma este DTO con:
 *   CreateHabitData::fromArray([...$request->validated(), 'user_id' => auth()->id()])
 */
final readonly class CreateHabitData
{
    public function __construct(
        public int $userId,
        public string $name,
        public string $habitNature,
        public string $desireType,
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
            userId: (int) $data['user_id'],
            name: (string) $data['name'],
            habitNature: (string) $data['habit_nature'],
            desireType: (string) $data['desire_type'],
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
}
