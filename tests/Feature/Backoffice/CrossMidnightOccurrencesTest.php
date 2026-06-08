<?php

namespace Tests\Feature\Backoffice;

use App\Models\Habit;
use App\Models\HabitOccurrence;
use App\Models\HabitSchedule;
use Core\BoundedContext\HabitOccurrences\Application\Actions\ExtendOccurrencesForHabit;
use Core\BoundedContext\HabitOccurrences\Application\Actions\RebuildOccurrencesForHabit;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossMidnightOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function habitWithSchedule(array $overrides): Habit
    {
        $habit = Habit::withoutEvents(fn () => Habit::factory()->build()->create());

        HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->create(array_merge([
            'habit_id' => $habit->habit_id,
            'recurrence_type' => 'daily',
            'starts_from' => '2026-05-26',
            'is_active' => true,
        ], $overrides)));

        return $habit;
    }

    public function test_rebuild_sets_end_date_to_next_day_for_cross_midnight_schedule(): void
    {
        $habit = $this->habitWithSchedule(['start_time' => '23:00', 'end_time' => '07:00']);

        app(RebuildOccurrencesForHabit::class)(HabitId::from($habit->habit_id), new DateTimeImmutable('2026-05-26'));

        $occurrences = HabitOccurrence::where('habit_id', $habit->habit_id)->get();
        $this->assertGreaterThan(0, $occurrences->count());

        foreach ($occurrences as $occ) {
            $expected = $occ->occurrence_date->copy()->addDay()->toDateString();
            $this->assertEquals($expected, $occ->end_date?->toDateString());
        }
    }

    public function test_rebuild_sets_end_date_equal_to_anchor_for_intraday_schedule(): void
    {
        $habit = $this->habitWithSchedule(['start_time' => '09:00', 'end_time' => '17:00']);

        app(RebuildOccurrencesForHabit::class)(HabitId::from($habit->habit_id), new DateTimeImmutable('2026-05-26'));

        $occurrences = HabitOccurrence::where('habit_id', $habit->habit_id)->get();
        $this->assertGreaterThan(0, $occurrences->count());

        foreach ($occurrences as $occ) {
            $this->assertEquals($occ->occurrence_date->toDateString(), $occ->end_date?->toDateString());
        }
    }

    public function test_cross_midnight_does_not_change_anchor_dates(): void
    {
        $habit = $this->habitWithSchedule([
            'recurrence_type' => 'weekly',
            'days_of_week' => [1, 3, 5],
            'start_time' => '23:00',
            'end_time' => '07:00',
        ]);

        app(RebuildOccurrencesForHabit::class)(HabitId::from($habit->habit_id), new DateTimeImmutable('2026-05-26'));

        $occurrences = HabitOccurrence::where('habit_id', $habit->habit_id)->get();
        $this->assertGreaterThan(0, $occurrences->count());

        foreach ($occurrences as $occ) {
            $this->assertContains($occ->occurrence_date->dayOfWeek, [1, 3, 5]);
            $this->assertEquals($occ->occurrence_date->copy()->addDay()->toDateString(), $occ->end_date?->toDateString());
        }
    }

    public function test_editing_times_to_cross_midnight_recomputes_end_date_on_rebuild(): void
    {
        $habit = $this->habitWithSchedule(['start_time' => '09:00', 'end_time' => '17:00']);
        app(RebuildOccurrencesForHabit::class)(HabitId::from($habit->habit_id), new DateTimeImmutable('2026-05-26'));

        HabitSchedule::withoutEvents(fn () => HabitSchedule::where('habit_id', $habit->habit_id)
            ->update(['start_time' => '23:00', 'end_time' => '07:00']));

        app(RebuildOccurrencesForHabit::class)(HabitId::from($habit->habit_id), new DateTimeImmutable('2026-05-26'));

        foreach (HabitOccurrence::where('habit_id', $habit->habit_id)->get() as $occ) {
            $this->assertEquals($occ->occurrence_date->copy()->addDay()->toDateString(), $occ->end_date?->toDateString());
        }
    }

    public function test_extend_sets_end_date_for_cross_midnight_schedule(): void
    {
        $habit = $this->habitWithSchedule(['start_time' => '23:00', 'end_time' => '07:00']);

        app(ExtendOccurrencesForHabit::class)(HabitId::from($habit->habit_id), new DateTimeImmutable('2026-05-26'));

        $occurrences = HabitOccurrence::where('habit_id', $habit->habit_id)->get();
        $this->assertGreaterThan(0, $occurrences->count());

        foreach ($occurrences as $occ) {
            $this->assertEquals($occ->occurrence_date->copy()->addDay()->toDateString(), $occ->end_date?->toDateString());
        }
    }
}
