<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Actions;

use Core\BoundedContext\Habits\Application\DTOs\UpdateHabitData;
use Core\BoundedContext\Habits\Application\Responses\HabitResponse;
use Core\BoundedContext\Habits\Domain\Exceptions\HabitNameAlreadyTaken;
use Core\BoundedContext\Habits\Domain\Exceptions\HabitNotFound;
use Core\BoundedContext\Habits\Domain\HabitRepository;
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

final readonly class UpdateHabit
{
    public function __construct(private HabitRepository $repository) {}

    public function __invoke(UpdateHabitData $data): HabitResponse
    {
        $habitId = HabitId::from($data->habitId);
        $userId = UserId::from($data->userId);

        $habit = $this->repository->findForUser($habitId, $userId);

        if ($habit === null) {
            throw HabitNotFound::withId($habitId);
        }

        $name = HabitName::from($data->name);

        if ($this->repository->nameExistsForUser($name, $userId, $habitId)) {
            throw HabitNameAlreadyTaken::forName($name);
        }

        $habit->update(
            name: $name,
            habitNature: HabitNature::from($data->habitNature),
            desireType: DesireType::from($data->desireType),
            description: $data->description !== null ? HabitDescription::from($data->description) : null,
            // color: si el DTO no lo trae, la entidad lo re-deriva del
            // nuevo habitNature — invariante de dominio.
            color: $data->color !== null ? HexColor::from($data->color) : null,
            implementationIntention: $data->implementationIntention !== null
                ? ImplementationIntention::from($data->implementationIntention)
                : null,
            location: $data->location !== null ? HabitLocation::from($data->location) : null,
            cue: $data->cue !== null ? HabitCue::from($data->cue) : null,
            reframe: $data->reframe !== null ? Reframe::from($data->reframe) : null,
        );

        if ($data->isActive !== null) {
            if ($data->isActive) {
                $habit->activate();
            } else {
                $habit->deactivate();
            }
        }

        $this->repository->save($habit);

        return HabitResponse::fromHabit($habit);
    }
}
