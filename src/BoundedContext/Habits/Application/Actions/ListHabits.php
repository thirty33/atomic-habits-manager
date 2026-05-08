<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Actions;

use Core\BoundedContext\Habits\Application\DTOs\ListHabitsData;
use Core\BoundedContext\Habits\Application\Responses\HabitsPaginatedResponse;
use Core\BoundedContext\Habits\Domain\Criteria\HabitsCriteria;
use Core\BoundedContext\Habits\Domain\Criteria\HabitsSort;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\DesireType;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitNature;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;

final readonly class ListHabits
{
    public function __construct(private HabitRepository $repository) {}

    public function __invoke(ListHabitsData $data): HabitsPaginatedResponse
    {
        $criteria = HabitsCriteria::forUser(UserId::from($data->userId))
            ->withSearch($data->search)
            ->withSort(HabitsSort::by($data->sortField, $data->sortDirection))
            ->withPage($data->page)
            ->withPerPage($data->perPage);

        if ($data->habitNature !== null) {
            $criteria = $criteria->withNature(HabitNature::from($data->habitNature));
        }

        if ($data->desireType !== null) {
            $criteria = $criteria->withDesireType(DesireType::from($data->desireType));
        }

        if ($data->isActive !== null) {
            $criteria = $criteria->withIsActive($data->isActive);
        }

        $page = $this->repository->matching($criteria);

        return HabitsPaginatedResponse::fromPage($page);
    }
}
