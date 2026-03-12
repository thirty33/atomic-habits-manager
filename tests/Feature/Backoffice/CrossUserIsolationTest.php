<?php

namespace Tests\Feature\Backoffice;

use App\Models\Habit;
use App\Models\HabitSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrossUserIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;

    private User $userB;

    private Habit $habitA;

    private HabitSchedule $scheduleA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();

        $this->habitA = Habit::factory()->build()->create([
            'user_id' => $this->userA->user_id,
            'name' => 'Habito de A',
        ]);

        $this->scheduleA = HabitSchedule::factory()->weekly([1, 2, 3, 4, 5])->create([
            'habit_id' => $this->habitA->habit_id,
        ]);
    }

    // -----------------------------------------------------------------------
    // Habits — read isolation
    // -----------------------------------------------------------------------

    public function test_user_b_cannot_see_habits_of_user_a(): void
    {
        $habitB = Habit::factory()->build()->create([
            'user_id' => $this->userB->user_id,
            'name' => 'Habito de B',
        ]);

        $response = $this->actingAs($this->userB)->get(route('backoffice.habits.json'));
        $response->assertOk();

        $names = collect($response->json('table_data.data'))->pluck('name')->toArray();
        $this->assertContains('Habito de B', $names);
        $this->assertNotContains('Habito de A', $names);
    }

    // -----------------------------------------------------------------------
    // Habits — update isolation
    // -----------------------------------------------------------------------

    public function test_user_b_cannot_update_habit_of_user_a(): void
    {
        $response = $this->actingAs($this->userB)->put(
            route('backoffice.habits.update', $this->habitA->habit_id),
            [
                'name' => 'Hackeado',
                'habit_nature' => 'build',
                'desire_type' => 'want',
            ]
        );

        $response->assertNotFound();

        $this->habitA->refresh();
        $this->assertEquals('Habito de A', $this->habitA->name);
    }

    // -----------------------------------------------------------------------
    // Habits — delete isolation
    // -----------------------------------------------------------------------

    public function test_user_b_cannot_delete_habit_of_user_a(): void
    {
        $response = $this->actingAs($this->userB)->delete(
            route('backoffice.habits.destroy', $this->habitA->habit_id)
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('habits', [
            'habit_id' => $this->habitA->habit_id,
            'deleted_at' => null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Schedules — create isolation
    // -----------------------------------------------------------------------

    public function test_user_b_cannot_create_schedule_for_habit_of_user_a(): void
    {
        $response = $this->actingAs($this->userB)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habitA->habit_id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'recurrence_type' => 'daily',
            'starts_from' => '2026-03-01',
        ]);

        $response->assertForbidden();

        $this->assertEquals(
            1,
            HabitSchedule::where('habit_id', $this->habitA->habit_id)->count(),
            'UserB should not be able to create schedules on UserA\'s habit'
        );
    }

    // -----------------------------------------------------------------------
    // Schedules — update isolation
    // -----------------------------------------------------------------------

    public function test_user_b_cannot_update_schedule_of_user_a(): void
    {
        $response = $this->actingAs($this->userB)->put(
            route('backoffice.habit-schedules.update', $this->scheduleA->habit_schedule_id),
            [
                'start_time' => '23:00',
                'end_time' => '23:30',
                'recurrence_type' => 'daily',
                'starts_from' => '2026-03-01',
            ]
        );

        $response->assertForbidden();

        $this->scheduleA->refresh();
        $this->assertNotEquals('23:00', $this->scheduleA->getRawOriginal('start_time'), 'UserB should not be able to update UserA\'s schedule');
    }

    // -----------------------------------------------------------------------
    // Cross-user data integrity
    // -----------------------------------------------------------------------

    public function test_user_b_actions_do_not_affect_user_a_data(): void
    {
        $habitB = Habit::factory()->build()->create([
            'user_id' => $this->userB->user_id,
            'name' => 'Habito de B',
        ]);

        HabitSchedule::factory()->daily()->create(['habit_id' => $habitB->habit_id]);

        // UserB deletes their own habit
        $this->actingAs($this->userB)->delete(
            route('backoffice.habits.destroy', $habitB->habit_id)
        );

        // UserA's data is untouched
        $this->assertDatabaseHas('habits', [
            'habit_id' => $this->habitA->habit_id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('habit_schedules', [
            'habit_schedule_id' => $this->scheduleA->habit_schedule_id,
        ]);
    }

    public function test_action_urls_point_to_own_resources_only(): void
    {
        Habit::factory()->build()->create(['user_id' => $this->userB->user_id]);

        $response = $this->actingAs($this->userB)->get(route('backoffice.habits.json'));
        $response->assertOk();

        $habits = collect($response->json('table_data.data'));

        $habits->each(function ($habit) {
            // update_action URL should contain the habit's own ID
            $this->assertStringContainsString(
                (string) $habit['habit_id'],
                $habit['update_action']['url']
            );
            $this->assertStringContainsString(
                (string) $habit['habit_id'],
                $habit['delete_action']['url']
            );

            // Should NOT contain userA's habit ID
            $this->assertStringNotContainsString(
                (string) $this->habitA->habit_id,
                $habit['update_action']['url']
            );
        });
    }
}
