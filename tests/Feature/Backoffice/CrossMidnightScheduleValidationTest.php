<?php

namespace Tests\Feature\Backoffice;

use App\Models\Habit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossMidnightScheduleValidationTest extends TestCase
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

    public function test_store_accepts_cross_midnight_window(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '23:00',
            'end_time' => '07:00',
            'recurrence_type' => 'daily',
            'starts_from' => '2026-03-01',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('habit_schedules', [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '23:00:00',
            'end_time' => '07:00:00',
        ]);
    }

    public function test_store_rejects_equal_start_and_end(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '08:00',
            'end_time' => '08:00',
            'recurrence_type' => 'daily',
            'starts_from' => '2026-03-01',
        ]);

        $response->assertSessionHasErrors('end_time');
    }

    public function test_sync_accepts_cross_midnight_item(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('backoffice.habits.schedules.sync', $this->habit->habit_id),
            ['schedules' => [
                ['start_time' => '23:00', 'end_time' => '07:00', 'recurrence_type' => 'daily', 'starts_from' => '2026-03-01'],
            ]]
        );

        $response->assertOk();
        $this->assertDatabaseHas('habit_schedules', [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '23:00:00',
            'end_time' => '07:00:00',
        ]);
    }

    public function test_sync_rejects_equal_start_and_end_item(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('backoffice.habits.schedules.sync', $this->habit->habit_id),
            ['schedules' => [
                ['start_time' => '08:00', 'end_time' => '08:00', 'recurrence_type' => 'daily', 'starts_from' => '2026-03-01'],
            ]]
        );

        $response->assertSessionHasErrors('schedules.0.end_time');
    }

    public function test_sync_mixed_rejects_only_the_equal_item(): void
    {
        $response = $this->actingAs($this->user)->put(
            route('backoffice.habits.schedules.sync', $this->habit->habit_id),
            ['schedules' => [
                ['start_time' => '23:00', 'end_time' => '07:00', 'recurrence_type' => 'daily', 'starts_from' => '2026-03-01'],
                ['start_time' => '08:00', 'end_time' => '08:00', 'recurrence_type' => 'daily', 'starts_from' => '2026-03-01'],
            ]]
        );

        $response->assertSessionHasErrors('schedules.1.end_time');
        $response->assertSessionDoesntHaveErrors('schedules.0.end_time');
    }
}
