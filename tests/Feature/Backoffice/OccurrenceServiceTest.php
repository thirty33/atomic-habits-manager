<?php

namespace Tests\Feature\Backoffice;

use App\Models\Habit;
use App\Models\HabitOccurrence;
use App\Models\HabitSchedule;
use App\Models\User;
use App\Services\Occurrences\Contracts\OccurrenceServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OccurrenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private OccurrenceServiceInterface $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(OccurrenceServiceInterface::class);
        $this->user = User::factory()->create();
    }

    // -----------------------------------------------------------------------
    // resolveDates — daily
    // -----------------------------------------------------------------------

    public function test_daily_generates_correct_dates(): void
    {
        $schedule = HabitSchedule::factory()->daily()->make([
            'starts_from' => '2026-03-01',
            'ends_at' => null,
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-05');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(5, $dates);
        $this->assertEquals('2026-03-01', $dates[0]->toDateString());
        $this->assertEquals('2026-03-05', $dates[4]->toDateString());
    }

    public function test_daily_respects_starts_from(): void
    {
        $schedule = HabitSchedule::factory()->daily()->make([
            'starts_from' => '2026-03-03',
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-05');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(3, $dates);
        $this->assertEquals('2026-03-03', $dates[0]->toDateString());
    }

    public function test_daily_respects_ends_at(): void
    {
        $schedule = HabitSchedule::factory()->daily()->make([
            'starts_from' => '2026-03-01',
            'ends_at' => '2026-03-03',
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-10');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(3, $dates);
        $this->assertEquals('2026-03-03', $dates[2]->toDateString());
    }

    // -----------------------------------------------------------------------
    // resolveDates — weekly
    // -----------------------------------------------------------------------

    public function test_weekly_generates_only_matching_days(): void
    {
        // days_of_week: 1=Mon, 3=Wed, 5=Fri
        $schedule = HabitSchedule::factory()->weekly([1, 3, 5])->make([
            'starts_from' => '2026-03-01',
        ]);

        // 2026-03-01 is Sunday, 02=Mon, 03=Tue, 04=Wed, 05=Thu, 06=Fri, 07=Sat
        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-07');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(3, $dates);
        $this->assertEquals('2026-03-02', $dates[0]->toDateString()); // Mon
        $this->assertEquals('2026-03-04', $dates[1]->toDateString()); // Wed
        $this->assertEquals('2026-03-06', $dates[2]->toDateString()); // Fri
    }

    public function test_weekly_with_empty_days_generates_zero(): void
    {
        $schedule = HabitSchedule::factory()->weekly([])->make([
            'starts_from' => '2026-03-01',
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-31');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(0, $dates);
    }

    public function test_weekly_with_null_days_generates_zero(): void
    {
        $schedule = HabitSchedule::factory()->make([
            'recurrence_type' => 'weekly',
            'days_of_week' => null,
            'starts_from' => '2026-03-01',
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-31');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(0, $dates);
    }

    // -----------------------------------------------------------------------
    // resolveDates — every_n_days
    // -----------------------------------------------------------------------

    public function test_every_n_days_generates_correct_intervals(): void
    {
        $schedule = HabitSchedule::factory()->everyNDays(3)->make([
            'starts_from' => '2026-03-01',
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-10');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        // Mar 1, 4, 7, 10
        $this->assertCount(4, $dates);
        $this->assertEquals('2026-03-01', $dates[0]->toDateString());
        $this->assertEquals('2026-03-04', $dates[1]->toDateString());
        $this->assertEquals('2026-03-07', $dates[2]->toDateString());
        $this->assertEquals('2026-03-10', $dates[3]->toDateString());
    }

    public function test_every_1_day_is_equivalent_to_daily(): void
    {
        $schedule = HabitSchedule::factory()->everyNDays(1)->make([
            'starts_from' => '2026-03-01',
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-05');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(5, $dates);
    }

    public function test_every_n_days_starts_from_future(): void
    {
        $schedule = HabitSchedule::factory()->everyNDays(3)->make([
            'starts_from' => '2026-03-05',
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-10');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        // Mar 5, 8
        $this->assertCount(2, $dates);
        $this->assertEquals('2026-03-05', $dates[0]->toDateString());
        $this->assertEquals('2026-03-08', $dates[1]->toDateString());
    }

    // -----------------------------------------------------------------------
    // resolveDates — none (one-time)
    // -----------------------------------------------------------------------

    public function test_none_generates_one_occurrence_if_in_range(): void
    {
        $schedule = HabitSchedule::factory()->oneTime('2026-03-15')->make();

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-31');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(1, $dates);
        $this->assertEquals('2026-03-15', $dates[0]->toDateString());
    }

    public function test_none_generates_zero_if_outside_range(): void
    {
        $schedule = HabitSchedule::factory()->oneTime('2026-04-15')->make();

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-31');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(0, $dates);
    }

    // -----------------------------------------------------------------------
    // resolveDates — edge cases
    // -----------------------------------------------------------------------

    public function test_schedule_with_ends_at_in_past_generates_zero(): void
    {
        $schedule = HabitSchedule::factory()->daily()->make([
            'starts_from' => '2025-01-01',
            'ends_at' => '2025-06-01',
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-31');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(0, $dates);
    }

    public function test_schedule_with_future_starts_from(): void
    {
        $schedule = HabitSchedule::factory()->daily()->make([
            'starts_from' => '2026-03-15',
        ]);

        $from = CarbonImmutable::parse('2026-03-01');
        $to = CarbonImmutable::parse('2026-03-20');

        $dates = $this->service->resolveDates($schedule, $from, $to);

        $this->assertCount(6, $dates); // 15, 16, 17, 18, 19, 20
        $this->assertEquals('2026-03-15', $dates[0]->toDateString());
    }

    // -----------------------------------------------------------------------
    // rebuildForHabit
    // -----------------------------------------------------------------------

    public function test_rebuild_generates_occurrences_for_daily_habit(): void
    {
        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'start_time' => '07:00',
            'end_time' => '07:30',
            'starts_from' => '2026-01-01',
        ]);

        $created = $this->service->rebuildForHabit($habit->habit_id);

        $this->assertGreaterThan(0, $created);

        $occurrences = HabitOccurrence::where('habit_id', $habit->habit_id)->get();
        $this->assertGreaterThan(0, $occurrences->count());

        // All generated occurrences should be today or future (>= today)
        foreach ($occurrences as $occurrence) {
            $this->assertGreaterThanOrEqual(now()->toDateString(), $occurrence->occurrence_date);
        }
    }

    public function test_rebuild_sets_flag_to_false(): void
    {
        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        $this->service->rebuildForHabit($habit->habit_id);

        $habit->refresh();
        $this->assertFalse($habit->needs_occurrence_rebuild);
    }

    public function test_rebuild_preserves_past_and_regenerates_today(): void
    {
        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        $schedule = HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        // Past occurrence
        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->service->rebuildForHabit($habit->habit_id);

        // Past preserved
        $this->assertDatabaseHas('habit_occurrences', [
            'habit_id' => $habit->habit_id,
            'occurrence_date' => now()->subDays(5)->toDateString(),
        ]);

        // Today regenerated
        $this->assertDatabaseHas('habit_occurrences', [
            'habit_id' => $habit->habit_id,
            'occurrence_date' => now()->toDateString(),
        ]);
    }

    public function test_rebuild_deletes_future_occurrences_and_regenerates(): void
    {
        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        $schedule = HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'start_time' => '06:00',
            'end_time' => '06:30',
            'starts_from' => '2026-01-01',
        ]);

        // Old future occurrence with different time
        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => now()->addDays(1)->toDateString(),
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $this->service->rebuildForHabit($habit->habit_id);

        // Old 08:00 should be gone, new occurrence should exist for that date
        $tomorrow = now()->addDays(1)->toDateString();
        $tomorrowOccurrences = HabitOccurrence::where('habit_id', $habit->habit_id)
            ->where('occurrence_date', $tomorrow)
            ->get();

        $this->assertCount(1, $tomorrowOccurrences);
        $this->assertStringStartsWith('06:00', $tomorrowOccurrences->first()->getRawOriginal('start_time'));
    }

    public function test_rebuild_ignores_inactive_schedules(): void
    {
        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        HabitSchedule::factory()->daily()->inactive()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        $created = $this->service->rebuildForHabit($habit->habit_id);

        $this->assertEquals(0, $created);
        $this->assertEquals(0, HabitOccurrence::where('habit_id', $habit->habit_id)->count());
    }

    public function test_rebuild_with_multiple_schedules(): void
    {
        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        // Weekday schedule
        HabitSchedule::factory()->weekly([1, 2, 3, 4, 5])->create([
            'habit_id' => $habit->habit_id,
            'start_time' => '06:00',
            'end_time' => '06:30',
            'starts_from' => '2026-01-01',
        ]);

        // Weekend schedule
        HabitSchedule::factory()->weekly([0, 6])->create([
            'habit_id' => $habit->habit_id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'starts_from' => '2026-01-01',
        ]);

        $created = $this->service->rebuildForHabit($habit->habit_id);

        $this->assertGreaterThan(0, $created);

        // Verify both time slots are present
        $occurrences = HabitOccurrence::where('habit_id', $habit->habit_id)->get();
        $rawTimes = $occurrences->pluck('start_time')
            ->map(fn ($t) => substr($t, 0, 5))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        $this->assertContains('06:00', $rawTimes);
        $this->assertContains('08:00', $rawTimes);
    }

    public function test_rebuild_with_zero_active_schedules_resets_flag(): void
    {
        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        $created = $this->service->rebuildForHabit($habit->habit_id);

        $this->assertEquals(0, $created);

        $habit->refresh();
        $this->assertFalse($habit->needs_occurrence_rebuild);
    }

    public function test_rebuild_is_idempotent(): void
    {
        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        $firstCount = $this->service->rebuildForHabit($habit->habit_id);

        // Re-set flag and run again
        $habit->updateQuietly(['needs_occurrence_rebuild' => true]);
        $secondCount = $this->service->rebuildForHabit($habit->habit_id);

        $this->assertEquals($firstCount, $secondCount);

        // Total today+future in DB should equal one run (no duplicates)
        $futureCount = HabitOccurrence::where('habit_id', $habit->habit_id)
            ->where('occurrence_date', '>=', now()->toDateString())
            ->count();
        $this->assertEquals($secondCount, $futureCount);
    }

    // -----------------------------------------------------------------------
    // extendForHabit
    // -----------------------------------------------------------------------

    public function test_extend_only_adds_new_occurrences_without_deleting(): void
    {
        // Silence observers to control data manually
        $habit = Habit::withoutEvents(fn () => Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]));

        $schedule = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]));

        // Existing occurrence 5 days from now
        $existingDate = now()->addDays(5)->toDateString();
        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => $existingDate,
        ]);

        $countBefore = HabitOccurrence::where('habit_id', $habit->habit_id)->count();

        $extended = $this->service->extendForHabit($habit->habit_id);

        $countAfter = HabitOccurrence::where('habit_id', $habit->habit_id)->count();

        $this->assertGreaterThan(0, $extended);
        $this->assertEquals($countBefore + $extended, $countAfter);
        $this->assertDatabaseHas('habit_occurrences', [
            'habit_id' => $habit->habit_id,
            'occurrence_date' => $existingDate,
        ]);
    }

    public function test_extend_does_nothing_when_coverage_is_full(): void
    {
        $habit = Habit::withoutEvents(fn () => Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]));

        $schedule = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]));

        // Last occurrence already 12+ months ahead
        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => now()->addMonths(12)->toDateString(),
        ]);

        $extended = $this->service->extendForHabit($habit->habit_id);

        $this->assertEquals(0, $extended);
    }

    // -----------------------------------------------------------------------
    // cleanupForDeletedHabit
    // -----------------------------------------------------------------------

    public function test_cleanup_deletes_future_occurrences_only(): void
    {
        $habit = Habit::withoutEvents(fn () => Habit::factory()->build()->create([
            'user_id' => $this->user->user_id,
        ]));

        $schedule = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
        ]));

        // Past
        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => now()->subDays(3)->toDateString(),
        ]);

        // Today
        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => now()->toDateString(),
        ]);

        // Future
        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => now()->addDays(5)->toDateString(),
        ]);

        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => now()->addDays(10)->toDateString(),
        ]);

        $deleted = $this->service->cleanupForDeletedHabit($habit->habit_id);

        $this->assertEquals(3, $deleted);

        // Past remains
        $this->assertDatabaseHas('habit_occurrences', [
            'habit_id' => $habit->habit_id,
            'occurrence_date' => now()->subDays(3)->toDateString(),
        ]);

        // Today and future gone
        $this->assertDatabaseMissing('habit_occurrences', [
            'habit_id' => $habit->habit_id,
            'occurrence_date' => now()->toDateString(),
        ]);
        $this->assertDatabaseMissing('habit_occurrences', [
            'habit_id' => $habit->habit_id,
            'occurrence_date' => now()->addDays(5)->toDateString(),
        ]);
    }
}
