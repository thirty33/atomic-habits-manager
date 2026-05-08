<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Responses;

use Core\BoundedContext\Habits\Domain\Habit;

/**
 * Snapshot inmutable de un Habit, en primitivas. Es lo que sale del
 * Use Case y lo que el controller serializa a JSON.
 *
 * No es la entidad: la entidad Habit nunca cruza la frontera Application.
 * Esto evita que el HTTP layer dependa de los VOs y permite que la
 * representación pública evolucione independiente del dominio.
 */
final readonly class HabitResponse
{
    public function __construct(
        public int $habitId,
        public int $userId,
        public string $name,
        public string $habitNature,
        public string $desireType,
        public bool $isActive,
        public bool $needsOccurrenceRebuild,
        public ?string $description,
        public ?string $color,
        public ?string $implementationIntention,
        public ?string $location,
        public ?string $cue,
        public ?string $reframe,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromHabit(Habit $habit): self
    {
        $habitId = $habit->habitId();

        if ($habitId === null) {
            throw new \LogicException('Cannot build HabitResponse from a Habit without id.');
        }

        return new self(
            habitId: $habitId->value(),
            userId: $habit->userId()->value(),
            name: $habit->name()->value(),
            habitNature: $habit->habitNature()->value(),
            desireType: $habit->desireType()->value(),
            isActive: $habit->isActive(),
            needsOccurrenceRebuild: $habit->needsOccurrenceRebuild(),
            description: $habit->description()?->value(),
            color: $habit->color()?->value(),
            implementationIntention: $habit->implementationIntention()?->value(),
            location: $habit->location()?->value(),
            cue: $habit->cue()?->value(),
            reframe: $habit->reframe()?->value(),
            createdAt: $habit->createdAt()?->format(\DateTimeInterface::ATOM),
            updatedAt: $habit->updatedAt()?->format(\DateTimeInterface::ATOM),
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
            'needs_occurrence_rebuild' => $this->needsOccurrenceRebuild,
            'description' => $this->description,
            'color' => $this->color,
            'implementation_intention' => $this->implementationIntention,
            'location' => $this->location,
            'cue' => $this->cue,
            'reframe' => $this->reframe,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
