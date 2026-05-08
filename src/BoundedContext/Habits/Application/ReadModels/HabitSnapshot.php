<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\ReadModels;

use Core\BoundedContext\Habits\Domain\Habit;

/**
 * Snapshot inmutable de un Habit consumible por otros BCs.
 *
 * Usado por:
 * - HabitOccurrences cuando necesita componer el habit en un read.
 * - DailyReports al renderizar el selector cross-BC.
 *
 * No expone DateTimeImmutable ni VOs — solo primitivas. Eso permite que
 * cualquier consumidor (incluido un job, un controller, un comando) lo
 * trate sin importar tipos de dominio.
 */
final readonly class HabitSnapshot
{
    public function __construct(
        public int $habitId,
        public int $userId,
        public string $name,
        public string $habitNature,
        public string $desireType,
        public bool $isActive,
        public ?string $color,
    ) {}

    public static function fromHabit(Habit $habit): self
    {
        $habitId = $habit->habitId();

        if ($habitId === null) {
            throw new \LogicException('Cannot build HabitSnapshot from a Habit without id.');
        }

        return new self(
            habitId: $habitId->value(),
            userId: $habit->userId()->value(),
            name: $habit->name()->value(),
            habitNature: $habit->habitNature()->value(),
            desireType: $habit->desireType()->value(),
            isActive: $habit->isActive(),
            color: $habit->color()?->value(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'habit_id' => $this->habitId,
            'user_id' => $this->userId,
            'name' => $this->name,
            'habit_nature' => $this->habitNature,
            'desire_type' => $this->desireType,
            'is_active' => $this->isActive,
            'color' => $this->color,
        ];
    }
}
