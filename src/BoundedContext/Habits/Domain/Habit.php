<?php

namespace Core\BoundedContext\Habits\Domain;

use Core\BoundedContext\Habits\Domain\Events\HabitWasCreated;
use Core\BoundedContext\Habits\Domain\Events\HabitWasRestored;
use Core\BoundedContext\Habits\Domain\Events\HabitWasSoftDeleted;
use Core\BoundedContext\Habits\Domain\Events\HabitWasUpdated;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\DesireType;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitCue;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitDescription;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitLocation;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitName;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitNature;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HexColor;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\ImplementationIntention;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\Reframe;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\AggregateRoot;
use DateTimeImmutable;

final class Habit extends AggregateRoot
{
    private function __construct(
        private ?HabitId $habitId,
        private UserId $userId,
        private HabitName $name,
        private HabitNature $habitNature,
        private DesireType $desireType,
        private bool $isActive,
        private bool $needsOccurrenceRebuild,
        private ?HabitDescription $description,
        private ?HexColor $color,
        private ?ImplementationIntention $implementationIntention,
        private ?HabitLocation $location,
        private ?HabitCue $cue,
        private ?Reframe $reframe,
        private ?DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $updatedAt,
        private ?DateTimeImmutable $deletedAt,
    ) {}

    public static function create(
        UserId $userId,
        HabitName $name,
        HabitNature $habitNature,
        DesireType $desireType,
        ?HabitDescription $description = null,
        ?HexColor $color = null,
        ?ImplementationIntention $implementationIntention = null,
        ?HabitLocation $location = null,
        ?HabitCue $cue = null,
        ?Reframe $reframe = null,
    ): self {
        return new self(
            habitId: null,
            userId: $userId,
            name: $name,
            habitNature: $habitNature,
            desireType: $desireType,
            isActive: true,
            needsOccurrenceRebuild: true,
            description: $description,
            color: $color ?? HexColor::from($habitNature->color()),
            implementationIntention: $implementationIntention,
            location: $location,
            cue: $cue,
            reframe: $reframe,
            createdAt: null,
            updatedAt: null,
            deletedAt: null,
        );
    }

    public static function fromPrimitives(
        int $habitId,
        int $userId,
        string $name,
        string $habitNature,
        string $desireType,
        bool $isActive,
        bool $needsOccurrenceRebuild,
        ?string $description,
        ?string $color,
        ?string $implementationIntention,
        ?string $location,
        ?string $cue,
        ?string $reframe,
        ?string $createdAt,
        ?string $updatedAt,
        ?string $deletedAt,
    ): self {
        return new self(
            habitId: HabitId::from($habitId),
            userId: UserId::from($userId),
            name: HabitName::from($name),
            habitNature: HabitNature::from($habitNature),
            desireType: DesireType::from($desireType),
            isActive: $isActive,
            needsOccurrenceRebuild: $needsOccurrenceRebuild,
            description: $description !== null ? HabitDescription::from($description) : null,
            color: $color !== null ? HexColor::from($color) : null,
            implementationIntention: $implementationIntention !== null ? ImplementationIntention::from($implementationIntention) : null,
            location: $location !== null ? HabitLocation::from($location) : null,
            cue: $cue !== null ? HabitCue::from($cue) : null,
            reframe: $reframe !== null ? Reframe::from($reframe) : null,
            createdAt: $createdAt !== null ? new DateTimeImmutable($createdAt) : null,
            updatedAt: $updatedAt !== null ? new DateTimeImmutable($updatedAt) : null,
            deletedAt: $deletedAt !== null ? new DateTimeImmutable($deletedAt) : null,
        );
    }

    public function habitId(): ?HabitId
    {
        return $this->habitId;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function name(): HabitName
    {
        return $this->name;
    }

    public function habitNature(): HabitNature
    {
        return $this->habitNature;
    }

    public function desireType(): DesireType
    {
        return $this->desireType;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function needsOccurrenceRebuild(): bool
    {
        return $this->needsOccurrenceRebuild;
    }

    public function description(): ?HabitDescription
    {
        return $this->description;
    }

    public function color(): ?HexColor
    {
        return $this->color;
    }

    public function implementationIntention(): ?ImplementationIntention
    {
        return $this->implementationIntention;
    }

    public function location(): ?HabitLocation
    {
        return $this->location;
    }

    public function cue(): ?HabitCue
    {
        return $this->cue;
    }

    public function reframe(): ?Reframe
    {
        return $this->reframe;
    }

    public function isNew(): bool
    {
        return $this->habitId === null;
    }

    public function hasId(): bool
    {
        return $this->habitId !== null;
    }

    public function createdAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function recordCreatedAfterAssign(): void
    {
        $this->record(HabitWasCreated::now($this->habitId, $this->userId));
    }

    public function update(
        HabitName $name,
        HabitNature $habitNature,
        DesireType $desireType,
        ?HabitDescription $description = null,
        ?HexColor $color = null,
        ?ImplementationIntention $implementationIntention = null,
        ?HabitLocation $location = null,
        ?HabitCue $cue = null,
        ?Reframe $reframe = null,
    ): void {
        $this->name = $name;
        $this->habitNature = $habitNature;
        $this->desireType = $desireType;
        $this->description = $description;
        $this->color = $color ?? HexColor::from($habitNature->color());
        $this->implementationIntention = $implementationIntention;
        $this->location = $location;
        $this->cue = $cue;
        $this->reframe = $reframe;

        $this->record(HabitWasUpdated::now($this->habitId, $this->userId));
    }

    public function activate(): void
    {
        $this->isActive = true;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
    }

    public function softDelete(): void
    {
        $this->deletedAt = new DateTimeImmutable;
        $this->record(HabitWasSoftDeleted::now($this->habitId, $this->userId));
    }

    public function restore(): void
    {
        $this->deletedAt = null;
        $this->record(HabitWasRestored::now($this->habitId, $this->userId));
    }

    public function assignId(HabitId $id): void
    {
        if ($this->habitId !== null) {
            throw new \DomainException('Habit already has an ID.');
        }

        $this->habitId = $id;
    }

    public function markForOccurrenceRebuild(): void
    {
        $this->needsOccurrenceRebuild = true;
    }

    public function clearOccurrenceRebuildFlag(): void
    {
        $this->needsOccurrenceRebuild = false;
    }
}
