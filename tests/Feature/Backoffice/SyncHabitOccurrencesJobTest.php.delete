<?php

namespace Tests\Feature\Backoffice;

use App\Jobs\SyncHabitOccurrencesJob;
use App\Models\Habit;
use App\Models\HabitOccurrence;
use App\Models\HabitSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncHabitOccurrencesJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // -----------------------------------------------------------------------
    // Basic execution
    // -----------------------------------------------------------------------

    public function test_job_generates_occurrences_for_habit(): void
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

        $job = new SyncHabitOccurrencesJob($habit->habit_id);
        app()->call([$job, 'handle']);

        $this->assertGreaterThan(
            0,
            HabitOccurrence::where('habit_id', $habit->habit_id)->count()
        );
    }

    public function test_job_resets_rebuild_flag(): void
    {
        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        $job = new SyncHabitOccurrencesJob($habit->habit_id);
        app()->call([$job, 'handle']);

        $habit->refresh();
        $this->assertFalse($habit->needs_occurrence_rebuild);
    }

    // -----------------------------------------------------------------------
    // Idempotency
    // -----------------------------------------------------------------------

    public function test_double_execution_does_not_create_duplicates(): void
    {
        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        // First execution
        $job1 = new SyncHabitOccurrencesJob($habit->habit_id);
        app()->call([$job1, 'handle']);

        $countAfterFirst = HabitOccurrence::where('habit_id', $habit->habit_id)->count();

        // Re-set flag and run again
        $habit->updateQuietly(['needs_occurrence_rebuild' => true]);

        $job2 = new SyncHabitOccurrencesJob($habit->habit_id);
        app()->call([$job2, 'handle']);

        $countAfterSecond = HabitOccurrence::where('habit_id', $habit->habit_id)->count();

        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    // -----------------------------------------------------------------------
    // Time change scenario
    // -----------------------------------------------------------------------

    public function test_job_updates_occurrences_when_schedule_time_changes(): void
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

        // First build
        $job = new SyncHabitOccurrencesJob($habit->habit_id);
        app()->call([$job, 'handle']);

        // Simulate time change
        $schedule->updateQuietly(['start_time' => '07:00', 'end_time' => '07:30']);
        $habit->updateQuietly(['needs_occurrence_rebuild' => true]);

        // Rebuild
        $job2 = new SyncHabitOccurrencesJob($habit->habit_id);
        app()->call([$job2, 'handle']);

        // All future occurrences should have the new time
        $futureOccurrences = HabitOccurrence::where('habit_id', $habit->habit_id)
            ->where('occurrence_date', '>', now()->toDateString())
            ->get();

        foreach ($futureOccurrences as $occurrence) {
            $this->assertStringStartsWith('07:00', $occurrence->getRawOriginal('start_time'));
            $this->assertStringStartsWith('07:30', $occurrence->getRawOriginal('end_time'));
        }
    }
}
