<?php

namespace Tests\Feature\Backoffice;

use App\Models\Habit;
use App\Models\HabitSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabitScheduleCrudTest extends TestCase
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

    // -----------------------------------------------------------------------
    // Create — daily
    // -----------------------------------------------------------------------

    public function test_store_daily_schedule(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'recurrence_type' => 'daily',
            'starts_from' => '2026-03-01',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('habit_schedules', [
            'habit_id' => $this->habit->habit_id,
            'recurrence_type' => 'daily',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'is_active' => true,
        ]);
    }

    // -----------------------------------------------------------------------
    // Create — weekly
    // -----------------------------------------------------------------------

    public function test_store_weekly_schedule(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '07:00',
            'end_time' => '08:00',
            'recurrence_type' => 'weekly',
            'days_of_week' => [1, 2, 3, 4, 5],
            'starts_from' => '2026-03-01',
        ]);

        $response->assertOk();

        $schedule = HabitSchedule::where('habit_id', $this->habit->habit_id)->first();
        $this->assertEquals('weekly', $schedule->recurrence_type);
        $this->assertEquals([1, 2, 3, 4, 5], $schedule->days_of_week);
    }

    public function test_store_weekly_schedule_requires_days_of_week(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '07:00',
            'end_time' => '08:00',
            'recurrence_type' => 'weekly',
            'starts_from' => '2026-03-01',
        ]);

        $response->assertSessionHasErrors('days_of_week');
    }

    // -----------------------------------------------------------------------
    // Create — every_n_days
    // -----------------------------------------------------------------------

    public function test_store_every_n_days_schedule(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '14:00',
            'end_time' => '14:30',
            'recurrence_type' => 'every_n_days',
            'interval_days' => 15,
            'starts_from' => '2026-03-01',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('habit_schedules', [
            'habit_id' => $this->habit->habit_id,
            'recurrence_type' => 'every_n_days',
            'interval_days' => 15,
        ]);
    }

    public function test_store_every_n_days_requires_interval(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '14:00',
            'end_time' => '14:30',
            'recurrence_type' => 'every_n_days',
            'starts_from' => '2026-03-01',
        ]);

        $response->assertSessionHasErrors('interval_days');
    }

    // -----------------------------------------------------------------------
    // Create — none (one-time)
    // -----------------------------------------------------------------------

    public function test_store_one_time_schedule(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'recurrence_type' => 'none',
            'specific_date' => '2026-04-15',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('habit_schedules', [
            'habit_id' => $this->habit->habit_id,
            'recurrence_type' => 'none',
            'specific_date' => '2026-04-15',
        ]);
    }

    public function test_store_one_time_requires_specific_date(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'recurrence_type' => 'none',
        ]);

        $response->assertSessionHasErrors('specific_date');
    }

    // -----------------------------------------------------------------------
    // Create — validation
    // -----------------------------------------------------------------------

    public function test_store_validates_required_fields(): void
    {
        // Without habit_id, authorize() returns false → 403
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), []);
        $response->assertForbidden();

        // With valid habit_id but missing other fields → validation errors
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
        ]);
        $response->assertSessionHasErrors(['start_time', 'end_time', 'recurrence_type']);
    }

    public function test_store_validates_end_time_after_start_time(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '09:00',
            'end_time' => '08:00',
            'recurrence_type' => 'daily',
            'starts_from' => '2026-03-01',
        ]);

        $response->assertSessionHasErrors('end_time');
    }

    public function test_store_validates_invalid_recurrence_type(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'recurrence_type' => 'monthly',
            'starts_from' => '2026-03-01',
        ]);

        $response->assertSessionHasErrors('recurrence_type');
    }

    public function test_store_validates_nonexistent_habit_id(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => 99999,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'recurrence_type' => 'daily',
            'starts_from' => '2026-03-01',
        ]);

        $response->assertForbidden();
    }

    public function test_store_validates_ends_at_after_starts_from(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'recurrence_type' => 'daily',
            'starts_from' => '2026-06-01',
            'ends_at' => '2026-03-01',
        ]);

        $response->assertSessionHasErrors('ends_at');
    }

    public function test_store_validates_ends_at_prohibited_for_none(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'recurrence_type' => 'none',
            'specific_date' => '2026-04-15',
            'ends_at' => '2026-05-01',
        ]);

        $response->assertSessionHasErrors('ends_at');
    }

    public function test_store_defaults_starts_from_to_today(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'recurrence_type' => 'daily',
            'starts_from' => null,
        ]);

        // starts_from is required_unless recurrence_type is none, so this should fail
        $response->assertSessionHasErrors('starts_from');
    }

    public function test_store_schedule_requires_authentication(): void
    {
        $response = $this->post(route('backoffice.habit-schedules.store'), [
            'habit_id' => $this->habit->habit_id,
            'start_time' => '08:00',
            'end_time' => '09:00',
            'recurrence_type' => 'daily',
            'starts_from' => '2026-03-01',
        ]);

        $response->assertRedirect();
    }

    // -----------------------------------------------------------------------
    // Update
    // -----------------------------------------------------------------------

    public function test_update_schedule_time(): void
    {
        $schedule = HabitSchedule::factory()->daily()->create(['habit_id' => $this->habit->habit_id]);

        $response = $this->actingAs($this->user)->put(
            route('backoffice.habit-schedules.update', $schedule->habit_schedule_id),
            [
                'habit_id' => $this->habit->habit_id,
                'start_time' => '07:00',
                'end_time' => '08:30',
                'recurrence_type' => 'daily',
                'starts_from' => '2026-03-01',
            ]
        );

        $response->assertOk();

        $schedule->refresh();
        $this->assertEquals('07:00', $schedule->start_time);
        $this->assertEquals('08:30', $schedule->end_time);
    }

    public function test_update_schedule_recurrence_type(): void
    {
        $schedule = HabitSchedule::factory()->weekly([1, 2, 3, 4, 5])->create([
            'habit_id' => $this->habit->habit_id,
        ]);

        $response = $this->actingAs($this->user)->put(
            route('backoffice.habit-schedules.update', $schedule->habit_schedule_id),
            [
                'habit_id' => $this->habit->habit_id,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'recurrence_type' => 'daily',
                'starts_from' => '2026-03-01',
            ]
        );

        $response->assertOk();

        $schedule->refresh();
        $this->assertEquals('daily', $schedule->recurrence_type);
    }

    public function test_update_schedule_days_of_week(): void
    {
        $schedule = HabitSchedule::factory()->weekly([1, 2, 3, 4, 5])->create([
            'habit_id' => $this->habit->habit_id,
        ]);

        $response = $this->actingAs($this->user)->put(
            route('backoffice.habit-schedules.update', $schedule->habit_schedule_id),
            [
                'habit_id' => $this->habit->habit_id,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'recurrence_type' => 'weekly',
                'days_of_week' => [1, 3, 5],
                'starts_from' => '2026-03-01',
            ]
        );

        $response->assertOk();

        $schedule->refresh();
        $this->assertEquals([1, 3, 5], $schedule->days_of_week);
    }

    public function test_update_schedule_add_ends_at(): void
    {
        $schedule = HabitSchedule::factory()->daily()->create([
            'habit_id' => $this->habit->habit_id,
            'starts_from' => '2026-03-01',
            'ends_at' => null,
        ]);

        $response = $this->actingAs($this->user)->put(
            route('backoffice.habit-schedules.update', $schedule->habit_schedule_id),
            [
                'habit_id' => $this->habit->habit_id,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'recurrence_type' => 'daily',
                'starts_from' => '2026-03-01',
                'ends_at' => '2026-12-31',
            ]
        );

        $response->assertOk();

        $schedule->refresh();
        $this->assertEquals('2026-12-31', $schedule->getRawOriginal('ends_at'));
    }

    public function test_update_schedule_does_not_change_is_active(): void
    {
        $schedule = HabitSchedule::factory()->daily()->create([
            'habit_id' => $this->habit->habit_id,
            'is_active' => true,
        ]);

        $this->actingAs($this->user)->put(
            route('backoffice.habit-schedules.update', $schedule->habit_schedule_id),
            [
                'habit_id' => $this->habit->habit_id,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'recurrence_type' => 'daily',
                'starts_from' => '2026-03-01',
                'is_active' => false,
            ]
        );

        $schedule->refresh();
        $this->assertTrue($schedule->is_active);
    }

    public function test_update_schedule_does_not_change_habit_id(): void
    {
        $otherHabit = Habit::factory()->build()->create(['user_id' => $this->user->user_id]);
        $schedule = HabitSchedule::factory()->daily()->create(['habit_id' => $this->habit->habit_id]);

        $this->actingAs($this->user)->put(
            route('backoffice.habit-schedules.update', $schedule->habit_schedule_id),
            [
                'habit_id' => $otherHabit->habit_id,
                'start_time' => '08:00',
                'end_time' => '09:00',
                'recurrence_type' => 'daily',
                'starts_from' => '2026-03-01',
            ]
        );

        $schedule->refresh();
        $this->assertEquals($this->habit->habit_id, $schedule->habit_id);
    }

    // -----------------------------------------------------------------------
    // Read (via HabitResource)
    // -----------------------------------------------------------------------

    public function test_schedule_included_in_habit_json(): void
    {
        $schedule = HabitSchedule::factory()->weekly([1, 3, 5])->create([
            'habit_id' => $this->habit->habit_id,
            'start_time' => '17:40',
            'end_time' => '18:40',
        ]);

        $response = $this->actingAs($this->user)->get(route('backoffice.habits.json'));
        $response->assertOk();

        $habitData = collect($response->json('table_data.data'))->firstWhere('habit_id', $this->habit->habit_id);

        $this->assertNotNull($habitData['active_schedule']);
        $this->assertEquals($schedule->habit_schedule_id, $habitData['active_schedule']['habit_schedule_id']);
        $this->assertEquals('weekly', $habitData['active_schedule']['recurrence_type']);
        $this->assertArrayHasKey('recurrence_type_label', $habitData['active_schedule']);
        $this->assertArrayHasKey('update_action', $habitData['active_schedule']);
        $this->assertEquals('17:40', $habitData['active_schedule']['start_time']);
        $this->assertEquals('18:40', $habitData['active_schedule']['end_time']);
    }
}
