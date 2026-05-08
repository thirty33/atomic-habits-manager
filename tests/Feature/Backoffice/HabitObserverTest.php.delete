<?php

namespace Tests\Feature\Backoffice;

use App\Jobs\CleanupDeletedHabitOccurrencesJob;
use App\Jobs\SyncHabitOccurrencesJob;
use App\Models\Habit;
use App\Models\HabitSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class HabitObserverTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // -----------------------------------------------------------------------
    // HabitObserver — updated
    // -----------------------------------------------------------------------

    public function test_updating_habit_sets_rebuild_flag_and_dispatches_sync_job(): void
    {
        Queue::fake();

        $habit = Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]);

        $habit->update(['name' => 'Nuevo nombre']);

        $habit->refresh();
        $this->assertTrue($habit->needs_occurrence_rebuild);

        Queue::assertPushed(SyncHabitOccurrencesJob::class, function ($job) use ($habit) {
            return $job->habitId === $habit->habit_id;
        });
    }

    public function test_changing_rebuild_flag_directly_does_not_retrigger_observer(): void
    {
        Queue::fake();

        $habit = Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]);

        // Directly updating the flag should NOT dispatch a job
        $habit->update(['needs_occurrence_rebuild' => true]);

        Queue::assertNotPushed(SyncHabitOccurrencesJob::class);
    }

    // -----------------------------------------------------------------------
    // HabitObserver — deleted (soft delete)
    // -----------------------------------------------------------------------

    public function test_soft_deleting_habit_dispatches_cleanup_job(): void
    {
        Queue::fake();

        $habit = Habit::factory()->build()->create([
            'user_id' => $this->user->user_id,
        ]);

        $habit->delete();

        Queue::assertPushed(CleanupDeletedHabitOccurrencesJob::class, function ($job) use ($habit) {
            return $job->habitId === $habit->habit_id;
        });
    }

    // -----------------------------------------------------------------------
    // HabitObserver — restored
    // -----------------------------------------------------------------------

    public function test_restoring_habit_sets_rebuild_flag_and_dispatches_sync_job(): void
    {
        Queue::fake();

        $habit = Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]);

        $habit->delete();

        // Clear the queue from the delete event
        Queue::fake();

        $habit->restore();

        $habit->refresh();
        $this->assertTrue($habit->needs_occurrence_rebuild);

        Queue::assertPushed(SyncHabitOccurrencesJob::class, function ($job) use ($habit) {
            return $job->habitId === $habit->habit_id;
        });
    }

    // -----------------------------------------------------------------------
    // HabitScheduleObserver — created
    // -----------------------------------------------------------------------

    public function test_creating_schedule_flags_parent_habit_and_dispatches_sync(): void
    {
        Queue::fake();

        $habit = Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]);

        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        $habit->refresh();
        $this->assertTrue($habit->needs_occurrence_rebuild);

        Queue::assertPushed(SyncHabitOccurrencesJob::class, function ($job) use ($habit) {
            return $job->habitId === $habit->habit_id;
        });
    }

    // -----------------------------------------------------------------------
    // HabitScheduleObserver — updated
    // -----------------------------------------------------------------------

    public function test_updating_schedule_flags_parent_habit_and_dispatches_sync(): void
    {
        Queue::fake();

        $habit = Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]);

        $schedule = HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        // Reset flag after creation triggered it
        $habit->updateQuietly(['needs_occurrence_rebuild' => false]);
        Queue::fake();

        $schedule->update(['start_time' => '10:00']);

        $habit->refresh();
        $this->assertTrue($habit->needs_occurrence_rebuild);

        Queue::assertPushed(SyncHabitOccurrencesJob::class, function ($job) use ($habit) {
            return $job->habitId === $habit->habit_id;
        });
    }

    // -----------------------------------------------------------------------
    // HabitScheduleObserver — deleted
    // -----------------------------------------------------------------------

    public function test_deleting_schedule_flags_parent_habit_and_dispatches_sync(): void
    {
        Queue::fake();

        $habit = Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]);

        $schedule = HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        // Reset flag after creation triggered it
        $habit->updateQuietly(['needs_occurrence_rebuild' => false]);
        Queue::fake();

        $schedule->delete();

        $habit->refresh();
        $this->assertTrue($habit->needs_occurrence_rebuild);

        Queue::assertPushed(SyncHabitOccurrencesJob::class, function ($job) use ($habit) {
            return $job->habitId === $habit->habit_id;
        });
    }
}
