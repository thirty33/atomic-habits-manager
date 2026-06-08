<?php

namespace Tests\Feature\BoundedContext\HabitOccurrences\Infrastructure;

use App\Models\Habit;
use App\Models\HabitOccurrence as HabitOccurrenceModel;
use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrence;
use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrenceRepository;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\HabitOccurrenceId;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceTime;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabitOccurrencePersistenceTest extends TestCase
{
    use RefreshDatabase;

    private function repository(): HabitOccurrenceRepository
    {
        return app(HabitOccurrenceRepository::class);
    }

    private function newHabitId(): HabitId
    {
        $habit = Habit::withoutEvents(fn () => Habit::factory()->build()->create());

        return HabitId::from($habit->habit_id);
    }

    public function test_save_many_persists_end_date_for_cross_midnight_occurrence(): void
    {
        $habitId = $this->newHabitId();
        $occurrence = HabitOccurrence::schedule(
            $habitId,
            OccurrenceDate::fromString('2026-06-10'),
            new OccurrenceTime('23:00', '07:00'),
        );

        $this->repository()->saveMany([$occurrence]);

        $row = HabitOccurrenceModel::where('habit_id', $habitId->value())->firstOrFail();
        $this->assertEquals('2026-06-11', $row->end_date?->toDateString());
    }

    public function test_save_many_persists_end_date_equal_to_anchor_for_intraday(): void
    {
        $habitId = $this->newHabitId();
        $occurrence = HabitOccurrence::schedule(
            $habitId,
            OccurrenceDate::fromString('2026-06-10'),
            new OccurrenceTime('09:00', '17:00'),
        );

        $this->repository()->saveMany([$occurrence]);

        $row = HabitOccurrenceModel::where('habit_id', $habitId->value())->firstOrFail();
        $this->assertEquals('2026-06-10', $row->end_date?->toDateString());
    }

    public function test_find_round_trips_end_date_for_cross_midnight_occurrence(): void
    {
        $habitId = $this->newHabitId();
        $occurrence = HabitOccurrence::schedule(
            $habitId,
            OccurrenceDate::fromString('2026-06-10'),
            new OccurrenceTime('23:00', '07:00'),
        );
        $this->repository()->saveMany([$occurrence]);

        $id = (int) HabitOccurrenceModel::where('habit_id', $habitId->value())->value('habit_occurrence_id');
        $reconstituted = $this->repository()->find(HabitOccurrenceId::from($id));

        $this->assertNotNull($reconstituted);
        $this->assertSame('2026-06-11', $reconstituted->endDate()->toString());
    }
}
