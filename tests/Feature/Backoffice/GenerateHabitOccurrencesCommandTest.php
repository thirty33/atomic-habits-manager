<?php

namespace Tests\Feature\Backoffice;

use App\Jobs\BatchExtendOccurrencesJob;
use App\Jobs\BatchGenerateOccurrencesJob;
use App\Models\Habit;
use App\Models\HabitOccurrence;
use App\Models\HabitSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateHabitOccurrencesCommandTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // -----------------------------------------------------------------------
    // Rebuild dispatch
    // -----------------------------------------------------------------------

    public function test_dispatches_batch_generate_job_for_habits_needing_rebuild(): void
    {
        Queue::fake();

        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        $this->artisan('habits:generate-occurrences')
            ->assertSuccessful();

        Queue::assertPushed(BatchGenerateOccurrencesJob::class, function ($job) use ($habit) {
            return in_array($habit->habit_id, $job->habitIds);
        });
    }

    public function test_does_not_dispatch_rebuild_for_already_rebuilt_habits(): void
    {
        $habit = Habit::withoutEvents(fn () => Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]));

        HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]));

        Queue::fake();

        $this->artisan('habits:generate-occurrences')
            ->assertSuccessful();

        Queue::assertNotPushed(BatchGenerateOccurrencesJob::class);
    }

    public function test_ignores_inactive_habits(): void
    {
        Queue::fake();

        $habit = Habit::factory()->build()->inactive()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        $this->artisan('habits:generate-occurrences')
            ->assertSuccessful();

        Queue::assertNotPushed(BatchGenerateOccurrencesJob::class);
    }

    public function test_ignores_soft_deleted_habits(): void
    {
        Queue::fake();

        $habit = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);

        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        $habit->delete(); // soft delete

        $this->artisan('habits:generate-occurrences')
            ->assertSuccessful();

        Queue::assertNotPushed(BatchGenerateOccurrencesJob::class);
    }

    // -----------------------------------------------------------------------
    // Extend dispatch
    // -----------------------------------------------------------------------

    public function test_dispatches_extend_job_for_habits_nearing_expiration(): void
    {
        $habit = Habit::withoutEvents(fn () => Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]));

        $schedule = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]));

        // Last occurrence is only 2 months ahead (threshold is < 11 months)
        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => now()->addMonths(2)->toDateString(),
        ]);

        Queue::fake();

        $this->artisan('habits:generate-occurrences')
            ->assertSuccessful();

        Queue::assertPushed(BatchExtendOccurrencesJob::class, function ($job) use ($habit) {
            return in_array($habit->habit_id, $job->habitIds);
        });
    }

    public function test_does_not_extend_habits_with_full_coverage(): void
    {
        $habit = Habit::withoutEvents(fn () => Habit::factory()->build()->rebuilt()->create([
            'user_id' => $this->user->user_id,
        ]));

        $schedule = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->daily()->create([
            'habit_id' => $habit->habit_id,
            'starts_from' => '2026-01-01',
        ]));

        // Last occurrence is 12 months ahead — no extension needed
        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => now()->addMonths(12)->toDateString(),
        ]);

        Queue::fake();

        $this->artisan('habits:generate-occurrences')
            ->assertSuccessful();

        Queue::assertNotPushed(BatchExtendOccurrencesJob::class);
    }

    // -----------------------------------------------------------------------
    // Chunking
    // -----------------------------------------------------------------------

    public function test_chunks_habits_by_specified_size(): void
    {
        Queue::fake();

        // Create 5 habits needing rebuild
        for ($i = 0; $i < 5; $i++) {
            $habit = Habit::factory()->build()->needsRebuild()->create([
                'user_id' => $this->user->user_id,
            ]);
            HabitSchedule::factory()->daily()->create([
                'habit_id' => $habit->habit_id,
                'starts_from' => '2026-01-01',
            ]);
        }

        $this->artisan('habits:generate-occurrences', ['--chunk' => 2])
            ->assertSuccessful();

        // 5 habits / 2 per chunk = 3 jobs
        Queue::assertPushed(BatchGenerateOccurrencesJob::class, 3);
    }

    // -----------------------------------------------------------------------
    // Multiple users
    // -----------------------------------------------------------------------

    public function test_processes_habits_from_all_users(): void
    {
        Queue::fake();

        $userB = User::factory()->create();

        $habitA = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $this->user->user_id,
        ]);
        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habitA->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        $habitB = Habit::factory()->build()->needsRebuild()->create([
            'user_id' => $userB->user_id,
        ]);
        HabitSchedule::factory()->daily()->create([
            'habit_id' => $habitB->habit_id,
            'starts_from' => '2026-01-01',
        ]);

        $this->artisan('habits:generate-occurrences')
            ->assertSuccessful();

        Queue::assertPushed(BatchGenerateOccurrencesJob::class, function ($job) use ($habitA, $habitB) {
            $ids = $job->habitIds;

            return in_array($habitA->habit_id, $ids) && in_array($habitB->habit_id, $ids);
        });
    }
}
