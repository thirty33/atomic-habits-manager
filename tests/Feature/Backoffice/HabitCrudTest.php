<?php

namespace Tests\Feature\Backoffice;

use App\Enums\HabitNature;
use App\Models\Habit;
use App\Models\HabitSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HabitCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // -----------------------------------------------------------------------
    // Create
    // -----------------------------------------------------------------------

    public function test_store_habit_with_minimum_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habits.store'), [
            'name' => 'Meditar',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('habits', [
            'user_id' => $this->user->user_id,
            'name' => 'Meditar',
            'habit_nature' => 'build',
            'desire_type' => 'want',
            'is_active' => true,
        ]);
    }

    public function test_store_habit_with_all_optional_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habits.store'), [
            'name' => 'Ejercicio',
            'habit_nature' => 'build',
            'desire_type' => 'need',
            'description' => 'Rutina de gimnasio',
            'implementation_intention' => 'Yo haré ejercicio a las 7am en el gimnasio',
            'location' => 'Gimnasio cerca de casa',
            'cue' => 'Al sonar la alarma',
            'reframe' => 'Tengo la voluntad de ejercitarme',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('habits', [
            'user_id' => $this->user->user_id,
            'name' => 'Ejercicio',
            'description' => 'Rutina de gimnasio',
            'implementation_intention' => 'Yo haré ejercicio a las 7am en el gimnasio',
            'location' => 'Gimnasio cerca de casa',
            'cue' => 'Al sonar la alarma',
            'reframe' => 'Tengo la voluntad de ejercitarme',
        ]);
    }

    public function test_store_habit_derives_color_from_nature_build(): void
    {
        $this->actingAs($this->user)->post(route('backoffice.habits.store'), [
            'name' => 'Leer',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ]);

        $this->assertDatabaseHas('habits', [
            'name' => 'Leer',
            'color' => HabitNature::BUILD->color(),
        ]);
    }

    public function test_store_habit_derives_color_from_nature_break(): void
    {
        $this->actingAs($this->user)->post(route('backoffice.habits.store'), [
            'name' => 'Dejar redes sociales',
            'habit_nature' => 'break',
            'desire_type' => 'need',
        ]);

        $this->assertDatabaseHas('habits', [
            'name' => 'Dejar redes sociales',
            'color' => HabitNature::BREAK->color(),
        ]);
    }

    public function test_store_habit_returns_habit_id_in_extra(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habits.store'), [
            'name' => 'Journaling',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['extra' => ['habit_id']]);

        $habitId = $response->json('extra.habit_id');
        $this->assertNotNull($habitId);
        $this->assertDatabaseHas('habits', ['habit_id' => $habitId]);
    }

    public function test_store_habit_validates_required_name(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habits.store'), [
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_habit_validates_unique_name_per_user(): void
    {
        Habit::factory()->create(['user_id' => $this->user->user_id, 'name' => 'Meditar']);

        $response = $this->actingAs($this->user)->post(route('backoffice.habits.store'), [
            'name' => 'Meditar',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_habit_allows_same_name_for_different_user(): void
    {
        $otherUser = User::factory()->create();
        Habit::factory()->create(['user_id' => $otherUser->user_id, 'name' => 'Meditar']);

        $response = $this->actingAs($this->user)->post(route('backoffice.habits.store'), [
            'name' => 'Meditar',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('habits', [
            'user_id' => $this->user->user_id,
            'name' => 'Meditar',
        ]);
    }

    public function test_store_habit_validates_invalid_habit_nature(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habits.store'), [
            'name' => 'Test',
            'habit_nature' => 'invalid',
            'desire_type' => 'want',
        ]);

        $response->assertSessionHasErrors('habit_nature');
    }

    public function test_store_habit_validates_invalid_desire_type(): void
    {
        $response = $this->actingAs($this->user)->post(route('backoffice.habits.store'), [
            'name' => 'Test',
            'habit_nature' => 'build',
            'desire_type' => 'invalid',
        ]);

        $response->assertSessionHasErrors('desire_type');
    }

    public function test_store_habit_requires_authentication(): void
    {
        $response = $this->post(route('backoffice.habits.store'), [
            'name' => 'Test',
            'habit_nature' => 'build',
            'desire_type' => 'want',
        ]);

        $response->assertRedirect();
    }

    // -----------------------------------------------------------------------
    // Update
    // -----------------------------------------------------------------------

    public function test_update_habit_name(): void
    {
        $habit = Habit::factory()->build()->create(['user_id' => $this->user->user_id, 'name' => 'Meditar']);

        $response = $this->actingAs($this->user)->put(
            route('backoffice.habits.update', $habit->habit_id),
            [
                'name' => 'Meditación profunda',
                'habit_nature' => 'build',
                'desire_type' => 'want',
                'is_active' => true,
            ]
        );

        $response->assertOk();
        $this->assertDatabaseHas('habits', [
            'habit_id' => $habit->habit_id,
            'name' => 'Meditación profunda',
        ]);
    }

    public function test_update_habit_recalculates_color_on_nature_change(): void
    {
        $habit = Habit::factory()->build()->create(['user_id' => $this->user->user_id]);

        $this->actingAs($this->user)->put(
            route('backoffice.habits.update', $habit->habit_id),
            [
                'name' => $habit->name,
                'habit_nature' => 'break',
                'desire_type' => 'need',
                'is_active' => true,
            ]
        );

        $this->assertDatabaseHas('habits', [
            'habit_id' => $habit->habit_id,
            'habit_nature' => 'break',
            'color' => HabitNature::BREAK->color(),
        ]);
    }

    public function test_update_habit_deactivate(): void
    {
        $habit = Habit::factory()->active()->build()->create(['user_id' => $this->user->user_id]);

        $this->actingAs($this->user)->put(
            route('backoffice.habits.update', $habit->habit_id),
            [
                'name' => $habit->name,
                'habit_nature' => 'build',
                'desire_type' => 'want',
                'is_active' => false,
            ]
        );

        $this->assertDatabaseHas('habits', [
            'habit_id' => $habit->habit_id,
            'is_active' => false,
        ]);
    }

    public function test_update_habit_validates_unique_name_excluding_self(): void
    {
        Habit::factory()->create(['user_id' => $this->user->user_id, 'name' => 'Ejercicio']);
        $habit = Habit::factory()->create(['user_id' => $this->user->user_id, 'name' => 'Meditar']);

        $response = $this->actingAs($this->user)->put(
            route('backoffice.habits.update', $habit->habit_id),
            [
                'name' => 'Ejercicio',
                'habit_nature' => 'build',
                'desire_type' => 'want',
                'is_active' => true,
            ]
        );

        $response->assertSessionHasErrors('name');
    }

    public function test_update_habit_allows_keeping_same_name(): void
    {
        $habit = Habit::factory()->build()->create(['user_id' => $this->user->user_id, 'name' => 'Meditar']);

        $response = $this->actingAs($this->user)->put(
            route('backoffice.habits.update', $habit->habit_id),
            [
                'name' => 'Meditar',
                'habit_nature' => 'build',
                'desire_type' => 'want',
                'is_active' => true,
                'description' => 'Actualizada',
            ]
        );

        $response->assertOk();
    }

    // -----------------------------------------------------------------------
    // Delete
    // -----------------------------------------------------------------------

    public function test_destroy_habit_soft_deletes(): void
    {
        $habit = Habit::factory()->build()->create(['user_id' => $this->user->user_id]);

        $response = $this->actingAs($this->user)->delete(
            route('backoffice.habits.destroy', $habit->habit_id)
        );

        $response->assertOk();
        $this->assertSoftDeleted('habits', ['habit_id' => $habit->habit_id]);
    }

    public function test_destroy_habit_not_visible_in_json_after_delete(): void
    {
        $habit = Habit::factory()->build()->create(['user_id' => $this->user->user_id]);

        $this->actingAs($this->user)->delete(
            route('backoffice.habits.destroy', $habit->habit_id)
        );

        $response = $this->actingAs($this->user)->get(route('backoffice.habits.json'));
        $response->assertOk();

        $ids = collect($response->json('table_data.data'))->pluck('habit_id')->toArray();
        $this->assertNotContains($habit->habit_id, $ids);
    }

    // -----------------------------------------------------------------------
    // Read (JSON)
    // -----------------------------------------------------------------------

    public function test_json_returns_only_own_habits(): void
    {
        Habit::factory()->build()->create(['user_id' => $this->user->user_id, 'name' => 'Mi habito']);
        $otherUser = User::factory()->create();
        Habit::factory()->build()->create(['user_id' => $otherUser->user_id, 'name' => 'Otro habito']);

        $response = $this->actingAs($this->user)->get(route('backoffice.habits.json'));
        $response->assertOk();

        $names = collect($response->json('table_data.data'))->pluck('name')->toArray();
        $this->assertContains('Mi habito', $names);
        $this->assertNotContains('Otro habito', $names);
    }

    public function test_json_includes_active_schedule(): void
    {
        $habit = Habit::factory()->build()->create(['user_id' => $this->user->user_id]);
        HabitSchedule::factory()->weekly([1, 2, 3, 4, 5])->create(['habit_id' => $habit->habit_id]);

        $response = $this->actingAs($this->user)->get(route('backoffice.habits.json'));
        $response->assertOk();

        $habitData = collect($response->json('table_data.data'))->firstWhere('habit_id', $habit->habit_id);
        $this->assertNotNull($habitData['active_schedule']);
        $this->assertEquals('weekly', $habitData['active_schedule']['recurrence_type']);
    }

    public function test_json_returns_null_active_schedule_when_none(): void
    {
        Habit::factory()->build()->create(['user_id' => $this->user->user_id]);

        $response = $this->actingAs($this->user)->get(route('backoffice.habits.json'));
        $response->assertOk();

        $habitData = collect($response->json('table_data.data'))->first();
        $this->assertNull($habitData['active_schedule']);
    }

    public function test_json_includes_translated_labels(): void
    {
        Habit::factory()->build()->create(['user_id' => $this->user->user_id]);

        $response = $this->actingAs($this->user)->get(route('backoffice.habits.json'));
        $response->assertOk();

        $habitData = collect($response->json('table_data.data'))->first();
        $this->assertArrayHasKey('habit_nature_label', $habitData);
        $this->assertArrayHasKey('desire_type_label', $habitData);
        $this->assertNotEmpty($habitData['habit_nature_label']);
        $this->assertNotEmpty($habitData['desire_type_label']);
    }

    public function test_json_includes_action_urls(): void
    {
        $habit = Habit::factory()->build()->create(['user_id' => $this->user->user_id]);

        $response = $this->actingAs($this->user)->get(route('backoffice.habits.json'));
        $response->assertOk();

        $habitData = collect($response->json('table_data.data'))->first();
        $this->assertArrayHasKey('update_action', $habitData);
        $this->assertArrayHasKey('delete_action', $habitData);
    }

    public function test_json_requires_authentication(): void
    {
        $response = $this->get(route('backoffice.habits.json'));
        $response->assertRedirect();
    }
}
