<?php

namespace Tests\Feature\Backoffice;

use App\Models\Habit;
use App\Models\HabitSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncHabitSchedulesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Habit $habit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->habit = Habit::factory()->build()->create(['user_id' => $this->user->user_id]);
    }

    public function test_sync_creates_updates_and_deletes_in_one_call(): void
    {
        $kept = HabitSchedule::factory()->daily()->create([
            'habit_id' => $this->habit->habit_id,
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);
        $removed = HabitSchedule::factory()->daily()->create(['habit_id' => $this->habit->habit_id]);

        $response = $this->actingAs($this->user)->put(
            route('backoffice.habits.schedules.sync', $this->habit->habit_id),
            ['schedules' => [
                // update existing
                [
                    'habit_schedule_id' => $kept->habit_schedule_id,
                    'start_time' => '07:00',
                    'end_time' => '08:30',
                    'recurrence_type' => 'daily',
                    'starts_from' => '2026-03-01',
                ],
                // create new
                [
                    'start_time' => '20:00',
                    'end_time' => '21:00',
                    'recurrence_type' => 'weekly',
                    'days_of_week' => [1, 3, 5],
                    'starts_from' => '2026-03-01',
                ],
                // $removed is absent → must be deleted
            ]]
        );

        $response->assertOk();

        $kept->refresh();
        $this->assertEquals('07:00', $kept->start_time);
        $this->assertEquals('08:30', $kept->end_time);

        $this->assertDatabaseMissing('habit_schedules', ['habit_schedule_id' => $removed->habit_schedule_id]);

        $this->assertEquals(2, HabitSchedule::where('habit_id', $this->habit->habit_id)->count());
        $this->assertDatabaseHas('habit_schedules', [
            'habit_id' => $this->habit->habit_id,
            'recurrence_type' => 'weekly',
            'start_time' => '20:00:00',
        ]);
    }

    public function test_sync_requires_ownership(): void
    {
        $other = User::factory()->create();

        $response = $this->actingAs($other)->put(
            route('backoffice.habits.schedules.sync', $this->habit->habit_id),
            ['schedules' => []]
        );

        $response->assertForbidden();
    }

    public function test_sync_validates_each_item(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('backoffice.habits.schedules.sync', $this->habit->habit_id),
            ['schedules' => [
                ['start_time' => '09:00', 'end_time' => '08:00', 'recurrence_type' => 'daily', 'starts_from' => '2026-03-01'],
            ]]
        );

        $response->assertSessionHasErrors('schedules.0.end_time');
    }

    public function test_sync_with_empty_set_deletes_all(): void
    {
        HabitSchedule::factory()->daily()->create(['habit_id' => $this->habit->habit_id]);

        $response = $this->actingAs($this->user)->put(
            route('backoffice.habits.schedules.sync', $this->habit->habit_id),
            ['schedules' => []]
        );

        $response->assertOk();
        $this->assertEquals(0, HabitSchedule::where('habit_id', $this->habit->habit_id)->count());
    }
}
