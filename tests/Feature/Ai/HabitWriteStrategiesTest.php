<?php

namespace Tests\Feature\Ai;

use App\Ai\Strategies\HabitCreateStrategy;
use App\Ai\Strategies\HabitDeleteStrategy;
use App\Ai\Strategies\HabitUpdateStrategy;
use App\Ai\Tools\CreateResourceTool;
use App\Ai\Tools\DeleteResourceTool;
use App\Ai\Tools\UpdateResourceTool;
use App\Models\Habit;
use App\Models\HabitSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class HabitWriteStrategiesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    // -----------------------------------------------------------------------
    // HabitCreateStrategy
    // -----------------------------------------------------------------------

    public function test_create_habit_with_minimum_required_fields(): void
    {
        $this->actingAs($this->user);

        $strategy = new HabitCreateStrategy;
        $result = $strategy->create($this->user->user_id, [
            'name' => 'Leer',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ]);

        $this->assertStringContainsString('Leer', $result);
        $this->assertDatabaseHas('habits', [
            'user_id' => $this->user->user_id,
            'name' => 'Leer',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ]);
        $this->assertDatabaseCount('habit_schedules', 0);
    }

    public function test_create_habit_with_all_optional_fields(): void
    {
        $this->actingAs($this->user);

        $strategy = new HabitCreateStrategy;
        $strategy->create($this->user->user_id, [
            'name' => 'Meditar',
            'habit_nature' => 'build',
            'desire_type' => 'need',
            'description' => 'Meditación diaria',
            'implementation_intention' => 'Al despertar en mi cuarto',
            'location' => 'Habitación',
            'cue' => 'Después de levantarme',
            'reframe' => 'Me da claridad mental',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('habits', [
            'user_id' => $this->user->user_id,
            'name' => 'Meditar',
            'description' => 'Meditación diaria',
            'implementation_intention' => 'Al despertar en mi cuarto',
            'location' => 'Habitación',
            'cue' => 'Después de levantarme',
            'is_active' => true,
        ]);
    }

    public function test_create_habit_with_daily_schedule(): void
    {
        $this->actingAs($this->user);

        $strategy = new HabitCreateStrategy;
        $result = $strategy->create($this->user->user_id, [
            'name' => 'Correr',
            'habit_nature' => 'build',
            'desire_type' => 'want',
            'schedule_recurrence_type' => 'daily',
            'schedule_start_time' => '07:00',
            'schedule_end_time' => '07:30',
        ]);

        $this->assertStringContainsString('Programación creada', $result);

        $habit = Habit::where('name', 'Correr')->first();
        $this->assertNotNull($habit);
        $this->assertDatabaseHas('habit_schedules', [
            'habit_id' => $habit->habit_id,
            'recurrence_type' => 'daily',
        ]);
    }

    public function test_create_habit_with_weekly_schedule_stores_days_as_array(): void
    {
        $this->actingAs($this->user);

        $strategy = new HabitCreateStrategy;
        $strategy->create($this->user->user_id, [
            'name' => 'Yoga',
            'habit_nature' => 'build',
            'desire_type' => 'want',
            'schedule_recurrence_type' => 'weekly',
            'schedule_start_time' => '07:00',
            'schedule_end_time' => '07:30',
            'schedule_days_of_week' => 'monday,wednesday,friday',
        ]);

        $habit = Habit::where('name', 'Yoga')->first();
        $schedule = HabitSchedule::where('habit_id', $habit->habit_id)->first();

        $this->assertNotNull($schedule);
        $this->assertEquals('weekly', $schedule->recurrence_type);
        $this->assertEqualsCanonicalizing(['monday', 'wednesday', 'friday'], $schedule->days_of_week);
    }

    public function test_create_habit_with_every_n_days_schedule(): void
    {
        $this->actingAs($this->user);

        $strategy = new HabitCreateStrategy;
        $strategy->create($this->user->user_id, [
            'name' => 'Ayuno',
            'habit_nature' => 'build',
            'desire_type' => 'need',
            'schedule_recurrence_type' => 'every_n_days',
            'schedule_start_time' => '12:00',
            'schedule_end_time' => '12:30',
            'schedule_interval_days' => 3,
        ]);

        $habit = Habit::where('name', 'Ayuno')->first();
        $schedule = HabitSchedule::where('habit_id', $habit->habit_id)->first();

        $this->assertEquals('every_n_days', $schedule->recurrence_type);
        $this->assertEquals(3, $schedule->interval_days);
    }

    public function test_create_habit_with_none_schedule_stores_specific_date(): void
    {
        $this->actingAs($this->user);

        $strategy = new HabitCreateStrategy;
        $strategy->create($this->user->user_id, [
            'name' => 'Evento especial',
            'habit_nature' => 'build',
            'desire_type' => 'neutral',
            'schedule_recurrence_type' => 'none',
            'schedule_start_time' => '10:00',
            'schedule_end_time' => '11:00',
            'schedule_specific_date' => '2026-03-15',
        ]);

        $habit = Habit::where('name', 'Evento especial')->first();
        $schedule = HabitSchedule::where('habit_id', $habit->habit_id)->first();

        $this->assertEquals('none', $schedule->recurrence_type);
        $this->assertEquals('2026-03-15', $schedule->specific_date->format('Y-m-d'));
    }

    public function test_create_throws_on_invalid_habit_nature(): void
    {
        $this->actingAs($this->user);

        $this->expectException(\ValueError::class);

        $strategy = new HabitCreateStrategy;
        $strategy->create($this->user->user_id, [
            'name' => 'Test',
            'habit_nature' => 'invalid_nature',
            'desire_type' => 'want',
        ]);
    }

    public function test_create_throws_on_invalid_schedule_recurrence_type(): void
    {
        $this->actingAs($this->user);

        $this->expectException(\ValueError::class);

        $strategy = new HabitCreateStrategy;
        $strategy->create($this->user->user_id, [
            'name' => 'Test',
            'habit_nature' => 'build',
            'desire_type' => 'want',
            'schedule_recurrence_type' => 'yearly',
        ]);
    }

    public function test_create_without_schedule_fields_creates_no_schedule(): void
    {
        $this->actingAs($this->user);

        $strategy = new HabitCreateStrategy;
        $strategy->create($this->user->user_id, [
            'name' => 'Sin horario',
            'habit_nature' => 'break',
            'desire_type' => 'need',
        ]);

        $this->assertDatabaseCount('habit_schedules', 0);
    }

    // -----------------------------------------------------------------------
    // HabitUpdateStrategy
    // -----------------------------------------------------------------------

    public function test_update_only_name_leaves_other_fields_intact(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit(['name' => 'Original', 'description' => 'Descripción original']);

        $strategy = new HabitUpdateStrategy;
        $result = $strategy->update($this->user->user_id, $habit->habit_id, [
            'name' => 'Nuevo nombre',
        ]);

        $this->assertStringContainsString('hábito', $result);
        $habit->refresh();
        $this->assertEquals('Nuevo nombre', $habit->name);
        $this->assertEquals('Descripción original', $habit->description);
    }

    public function test_update_is_active_false_persists_correctly(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit(['is_active' => true]);

        $strategy = new HabitUpdateStrategy;
        $strategy->update($this->user->user_id, $habit->habit_id, [
            'is_active' => false,
        ]);

        $habit->refresh();
        $this->assertFalse($habit->is_active);
    }

    public function test_update_habit_nature_also_updates_color(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit(['habit_nature' => 'build']);
        $originalColor = $habit->color;

        $strategy = new HabitUpdateStrategy;
        $strategy->update($this->user->user_id, $habit->habit_id, [
            'habit_nature' => 'break',
        ]);

        $habit->refresh();
        $this->assertEquals('break', $habit->habit_nature->value);
        $this->assertNotEquals($originalColor, $habit->color);
    }

    public function test_update_creates_new_schedule_when_no_schedule_id_provided(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit();

        $this->assertDatabaseCount('habit_schedules', 0);

        $strategy = new HabitUpdateStrategy;
        $result = $strategy->update($this->user->user_id, $habit->habit_id, [
            'schedule_recurrence_type' => 'daily',
            'schedule_start_time' => '08:00',
            'schedule_end_time' => '08:30',
        ]);

        $this->assertStringContainsString('nueva programación', $result);
        $this->assertDatabaseCount('habit_schedules', 1);
        $this->assertDatabaseHas('habit_schedules', [
            'habit_id' => $habit->habit_id,
            'recurrence_type' => 'daily',
        ]);
    }

    public function test_update_existing_schedule_with_schedule_id(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit();
        $schedule = $this->createSchedule($habit->habit_id, ['recurrence_type' => 'daily']);

        $strategy = new HabitUpdateStrategy;
        $result = $strategy->update($this->user->user_id, $habit->habit_id, [
            'schedule_id' => $schedule->habit_schedule_id,
            'schedule_recurrence_type' => 'weekly',
            'schedule_start_time' => '09:00',
            'schedule_end_time' => '09:30',
            'schedule_days_of_week' => 'monday,friday',
        ]);

        $this->assertStringContainsString((string) $schedule->habit_schedule_id, $result);
        $schedule->refresh();
        $this->assertEquals('weekly', $schedule->recurrence_type);
    }

    public function test_update_rejects_schedule_belonging_to_different_habit(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit();
        $otherHabit = $this->createHabit(['name' => 'Otro']);
        $scheduleOfOther = $this->createSchedule($otherHabit->habit_id);

        $strategy = new HabitUpdateStrategy;
        $result = $strategy->update($this->user->user_id, $habit->habit_id, [
            'schedule_id' => $scheduleOfOther->habit_schedule_id,
            'schedule_recurrence_type' => 'daily',
        ]);

        $this->assertStringContainsString('Error', $result);
        $scheduleOfOther->refresh();
        $this->assertEquals($otherHabit->habit_id, $scheduleOfOther->habit_id);
    }

    public function test_update_throws_when_habit_belongs_to_another_user(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit(['user_id' => $this->otherUser->user_id]);

        $this->expectException(ModelNotFoundException::class);

        $strategy = new HabitUpdateStrategy;
        $strategy->update($this->user->user_id, $habit->habit_id, ['name' => 'Hack']);
    }

    public function test_update_returns_message_when_no_fields_provided(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit();

        $strategy = new HabitUpdateStrategy;
        $result = $strategy->update($this->user->user_id, $habit->habit_id, []);

        $this->assertStringContainsString('No se proporcionaron campos', $result);
    }

    // -----------------------------------------------------------------------
    // HabitDeleteStrategy
    // -----------------------------------------------------------------------

    public function test_delete_habit_soft_deletes_it(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit(['name' => 'A eliminar']);

        $strategy = new HabitDeleteStrategy;
        $result = $strategy->delete($this->user->user_id, $habit->habit_id);

        $this->assertStringContainsString('A eliminar', $result);
        $this->assertSoftDeleted('habits', ['habit_id' => $habit->habit_id]);
    }

    public function test_delete_specific_schedule_leaves_habit_intact(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit();
        $schedule = $this->createSchedule($habit->habit_id);

        $strategy = new HabitDeleteStrategy;
        $result = $strategy->delete($this->user->user_id, $habit->habit_id, [
            'schedule_id' => $schedule->habit_schedule_id,
        ]);

        $this->assertStringContainsString((string) $schedule->habit_schedule_id, $result);
        $this->assertDatabaseMissing('habit_schedules', ['habit_schedule_id' => $schedule->habit_schedule_id]);
        $this->assertDatabaseHas('habits', ['habit_id' => $habit->habit_id, 'deleted_at' => null]);
    }

    public function test_delete_rejects_schedule_belonging_to_different_habit(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit();
        $otherHabit = $this->createHabit(['name' => 'Otro']);
        $scheduleOfOther = $this->createSchedule($otherHabit->habit_id);

        $strategy = new HabitDeleteStrategy;
        $result = $strategy->delete($this->user->user_id, $habit->habit_id, [
            'schedule_id' => $scheduleOfOther->habit_schedule_id,
        ]);

        $this->assertStringContainsString('Error', $result);
        $this->assertDatabaseHas('habit_schedules', ['habit_schedule_id' => $scheduleOfOther->habit_schedule_id]);
    }

    public function test_delete_habit_throws_when_belongs_to_another_user(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit(['user_id' => $this->otherUser->user_id]);

        $this->expectException(ModelNotFoundException::class);

        $strategy = new HabitDeleteStrategy;
        $strategy->delete($this->user->user_id, $habit->habit_id);
    }

    public function test_delete_schedule_throws_when_habit_belongs_to_another_user(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit(['user_id' => $this->otherUser->user_id]);
        $schedule = $this->createSchedule($habit->habit_id);

        $this->expectException(ModelNotFoundException::class);

        $strategy = new HabitDeleteStrategy;
        $strategy->delete($this->user->user_id, $habit->habit_id, [
            'schedule_id' => $schedule->habit_schedule_id,
        ]);
    }

    // -----------------------------------------------------------------------
    // Tool handle() — integration
    // These tests simulate what the AI does: calling tool->handle(Request)
    // -----------------------------------------------------------------------

    public function test_create_tool_handle_creates_habit_and_ignores_unknown_fields(): void
    {
        $this->actingAs($this->user);

        $tool = new CreateResourceTool(new HabitCreateStrategy);

        $request = new Request([
            'resource' => 'habits',
            'name' => 'Leer 30 min',
            'habit_nature' => 'build',
            'desire_type' => 'want',
            'unknown_field_from_ai' => 'should_be_ignored',
        ]);

        $result = $tool->handle($request);

        $this->assertStringContainsString('Leer 30 min', $result);
        $this->assertDatabaseHas('habits', [
            'user_id' => $this->user->user_id,
            'name' => 'Leer 30 min',
        ]);
    }

    public function test_create_tool_handle_creates_habit_with_schedule_in_one_request(): void
    {
        $this->actingAs($this->user);

        $tool = new CreateResourceTool(new HabitCreateStrategy);

        $request = new Request([
            'resource' => 'habits',
            'name' => 'Meditar',
            'habit_nature' => 'build',
            'desire_type' => 'need',
            'schedule_recurrence_type' => 'daily',
            'schedule_start_time' => '07:00',
            'schedule_end_time' => '07:30',
        ]);

        $result = $tool->handle($request);

        $this->assertStringContainsString('Meditar', $result);
        $this->assertStringContainsString('Programación creada', $result);

        $habit = Habit::where('name', 'Meditar')->first();
        $this->assertDatabaseHas('habit_schedules', ['habit_id' => $habit->habit_id]);
    }

    public function test_update_tool_handle_casts_id_to_integer_and_updates_habit(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit(['name' => 'Antes']);

        $tool = new UpdateResourceTool(new HabitUpdateStrategy);

        $request = new Request([
            'resource' => 'habits',
            'id' => (string) $habit->habit_id, // AI may send as string
            'name' => 'Después',
        ]);

        $tool->handle($request);

        $habit->refresh();
        $this->assertEquals('Después', $habit->name);
    }

    public function test_delete_tool_handle_without_schedule_id_deletes_full_habit(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit();

        $tool = new DeleteResourceTool(new HabitDeleteStrategy);

        $request = new Request([
            'resource' => 'habits',
            'id' => $habit->habit_id,
            // no schedule_id
        ]);

        $tool->handle($request);

        $this->assertSoftDeleted('habits', ['habit_id' => $habit->habit_id]);
    }

    public function test_delete_tool_handle_with_schedule_id_deletes_only_schedule(): void
    {
        $this->actingAs($this->user);

        $habit = $this->createHabit();
        $schedule = $this->createSchedule($habit->habit_id);

        $tool = new DeleteResourceTool(new HabitDeleteStrategy);

        $request = new Request([
            'resource' => 'habits',
            'id' => $habit->habit_id,
            'schedule_id' => $schedule->habit_schedule_id,
        ]);

        $tool->handle($request);

        $this->assertDatabaseMissing('habit_schedules', ['habit_schedule_id' => $schedule->habit_schedule_id]);
        $this->assertDatabaseHas('habits', ['habit_id' => $habit->habit_id, 'deleted_at' => null]);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function createHabit(array $overrides = []): Habit
    {
        return Habit::create(array_merge([
            'user_id' => $this->user->user_id,
            'name' => 'Hábito de prueba',
            'habit_nature' => 'build',
            'desire_type' => 'want',
            'color' => '#22C55E',
            'is_active' => false,
        ], $overrides));
    }

    private function createSchedule(int $habitId, array $overrides = []): HabitSchedule
    {
        return HabitSchedule::create(array_merge([
            'habit_id' => $habitId,
            'recurrence_type' => 'daily',
            'start_time' => '08:00',
            'end_time' => '08:30',
            'starts_from' => now()->toDateString(),
            'is_active' => true,
        ], $overrides));
    }
}
