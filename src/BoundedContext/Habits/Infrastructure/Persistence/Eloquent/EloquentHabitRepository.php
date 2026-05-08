<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Infrastructure\Persistence\Eloquent;

use App\Models\Habit as HabitModel;
use Core\BoundedContext\Habits\Domain\Criteria\HabitsCriteria;
use Core\BoundedContext\Habits\Domain\Criteria\HabitsPage;
use Core\BoundedContext\Habits\Domain\Habit;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Habits\Domain\Habits;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Illuminate\Support\Facades\DB;

final readonly class EloquentHabitRepository implements HabitRepository
{
    public function __construct(
        private HabitModel $model,
        private HabitsCriteriaTranslator $translator,
        private DomainEventBus $bus,
    ) {}

    public function save(Habit $habit): void
    {
        DB::transaction(function () use ($habit) {
            $isNew = $habit->isNew();

            $row = $isNew
                ? $this->model->newInstance()
                : $this->model->newQuery()->find($habit->habitId()->value());

            $row->fill($this->toAttributes($habit));
            $row->save();

            if ($isNew) {
                $habit->assignId(HabitId::from((int) $row->getKey()));
                $habit->recordCreatedAfterAssign();
            }

            $this->bus->publish(...$habit->pullDomainEvents());
        });
    }

    public function find(HabitId $id): ?Habit
    {
        $row = $this->model->newQuery()->find($id->value());

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function findForUser(HabitId $id, UserId $userId): ?Habit
    {
        $row = $this->model->newQuery()
            ->where('habit_id', $id->value())
            ->where('user_id', $userId->value())
            ->first();

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function delete(Habit $habit): void
    {
        DB::transaction(function () use ($habit) {
            $habitId = $habit->habitId();

            if ($habitId === null) {
                throw new \LogicException('Cannot delete a Habit without id.');
            }

            $this->model->newQuery()->where('habit_id', $habitId->value())->delete();

            $this->bus->publish(...$habit->pullDomainEvents());
        });
    }

    public function findAllForUser(UserId $userId): Habits
    {
        $rows = $this->model->newQuery()
            ->where('user_id', $userId->value())
            ->orderByDesc('created_at')
            ->get();

        $habits = $rows->map(fn (HabitModel $row) => $this->toDomain($row))->all();

        return new Habits($habits);
    }

    public function matching(HabitsCriteria $criteria): HabitsPage
    {
        $query = $this->translator->translate($this->model->newQuery(), $criteria);

        $paginator = $query->paginate(
            perPage: $criteria->perPage,
            page: $criteria->page,
        );

        $habits = collect($paginator->items())
            ->map(fn (HabitModel $row) => $this->toDomain($row))
            ->all();

        return new HabitsPage(
            items: new Habits($habits),
            total: (int) $paginator->total(),
            page: (int) $paginator->currentPage(),
            perPage: (int) $paginator->perPage(),
        );
    }

    public function findActiveForUser(UserId $userId): Habits
    {
        $rows = $this->model->newQuery()
            ->where('user_id', $userId->value())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $habits = $rows->map(fn (HabitModel $row) => $this->toDomain($row))->all();

        return new Habits($habits);
    }

    public function pendingRebuildIds(): array
    {
        return $this->model->newQuery()
            ->where('needs_occurrence_rebuild', true)
            ->pluck('habit_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function pendingExtensionIds(string $thresholdYmd): array
    {
        return $this->model->newQuery()
            ->where('is_active', true)
            ->whereExists(function ($query): void {
                $query->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('habit_occurrences')
                    ->whereColumn('habit_occurrences.habit_id', 'habits.habit_id')
                    ->whereNull('habit_occurrences.deleted_at');
            })
            ->whereRaw(
                '(SELECT MAX(scheduled_date) FROM habit_occurrences '
                .'WHERE habit_occurrences.habit_id = habits.habit_id AND habit_occurrences.deleted_at IS NULL) <= ?',
                [$thresholdYmd],
            )
            ->pluck('habit_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function markRebuilt(HabitId $id): void
    {
        $this->model->newQuery()
            ->where('habit_id', $id->value())
            ->update(['needs_occurrence_rebuild' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toAttributes(Habit $habit): array
    {
        return [
            'user_id' => $habit->userId()->value(),
            'name' => $habit->name()->value(),
            'habit_nature' => $habit->habitNature()->value(),
            'desire_type' => $habit->desireType()->value(),
            'description' => $habit->description()?->value(),
            'color' => $habit->color()?->value(),
            'implementation_intention' => $habit->implementationIntention()?->value(),
            'location' => $habit->location()?->value(),
            'cue' => $habit->cue()?->value(),
            'reframe' => $habit->reframe()?->value(),
            'is_active' => $habit->isActive(),
            'needs_occurrence_rebuild' => $habit->needsOccurrenceRebuild(),
        ];
    }

    private function toDomain(HabitModel $row): Habit
    {
        $attrs = $row->getAttributes();

        return Habit::fromPrimitives(
            habitId: (int) $attrs['habit_id'],
            userId: (int) $attrs['user_id'],
            name: (string) $attrs['name'],
            habitNature: (string) $attrs['habit_nature'],
            desireType: (string) $attrs['desire_type'],
            isActive: (bool) $attrs['is_active'],
            needsOccurrenceRebuild: (bool) ($attrs['needs_occurrence_rebuild'] ?? false),
            description: $this->nullableString($attrs, 'description'),
            color: $this->nullableString($attrs, 'color'),
            implementationIntention: $this->nullableString($attrs, 'implementation_intention'),
            location: $this->nullableString($attrs, 'location'),
            cue: $this->nullableString($attrs, 'cue'),
            reframe: $this->nullableString($attrs, 'reframe'),
            createdAt: $this->nullableString($attrs, 'created_at'),
            updatedAt: $this->nullableString($attrs, 'updated_at'),
            deletedAt: $this->nullableString($attrs, 'deleted_at'),
        );
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function nullableString(array $attrs, string $key): ?string
    {
        if (! array_key_exists($key, $attrs) || $attrs[$key] === null) {
            return null;
        }

        return (string) $attrs[$key];
    }
}
