<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Actions;

use Core\BoundedContext\Habits\Application\DTOs\CreateHabitData;
use Core\BoundedContext\Habits\Application\Responses\HabitResponse;
use Core\BoundedContext\Habits\Domain\Habit;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\DesireType;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitCue;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitDescription;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitLocation;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitName;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitNature;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HexColor;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\ImplementationIntention;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\Reframe;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

final readonly class CreateHabit
{
    public function __construct(private HabitRepository $repository) {}

    public function __invoke(CreateHabitData $data): HabitResponse
    {
        $habit = Habit::create(
            userId: UserId::from($data->userId),
            name: HabitName::from($data->name),
            habitNature: HabitNature::from($data->habitNature),
            desireType: DesireType::from($data->desireType),
            description: $data->description !== null ? HabitDescription::from($data->description) : null,
            // color: si el DTO no lo trae (caso normal del backoffice), la
            // entidad lo deriva del habitNature — invariante de dominio.
            // Solo se pasa explícito si el caller quiere sobrescribir el default.
            color: $data->color !== null ? HexColor::from($data->color) : null,
            implementationIntention: $data->implementationIntention !== null
                ? ImplementationIntention::from($data->implementationIntention)
                : null,
            location: $data->location !== null ? HabitLocation::from($data->location) : null,
            cue: $data->cue !== null ? HabitCue::from($data->cue) : null,
            reframe: $data->reframe !== null ? Reframe::from($data->reframe) : null,
        );

        $this->repository->save($habit);

        return HabitResponse::fromHabit($habit);
    }
}
