<?php

namespace Tests\Feature\Backoffice;

use App\Models\Habit;
use App\Models\HabitOccurrence;
use App\Models\HabitSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(route('backoffice.calendar.occurrences', [
            'start' => '2026-03-01',
            'end' => '2026-03-31',
        ]))->assertUnauthorized();
    }

    public function test_requires_start_and_end_params(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.occurrences'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start', 'end']);
    }

    public function test_end_must_be_after_start(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.occurrences', [
                'start' => '2026-03-31',
                'end' => '2026-03-01',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end']);
    }

    public function test_returns_occurrences_within_date_range(): void
    {
        $habit = Habit::withoutEvents(fn () => Habit::factory()->for($this->user)->create());

        $schedule = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->create([
            'habit_id' => $habit->habit_id,
        ]));

        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => '2026-03-10',
        ]);

        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => '2026-03-20',
        ]);

        // Outside range
        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => '2026-04-15',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.occurrences', [
                'start' => '2026-03-01',
                'end' => '2026-03-31',
            ]));

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_does_not_return_other_users_occurrences(): void
    {
        $otherUser = User::factory()->create();

        $myHabit = Habit::withoutEvents(fn () => Habit::factory()->for($this->user)->create());
        $otherHabit = Habit::withoutEvents(fn () => Habit::factory()->for($otherUser)->create());

        $mySchedule = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->create([
            'habit_id' => $myHabit->habit_id,
        ]));

        $otherSchedule = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->create([
            'habit_id' => $otherHabit->habit_id,
        ]));

        HabitOccurrence::factory()->create([
            'habit_id' => $myHabit->habit_id,
            'habit_schedule_id' => $mySchedule->habit_schedule_id,
            'occurrence_date' => '2026-03-10',
        ]);

        HabitOccurrence::factory()->create([
            'habit_id' => $otherHabit->habit_id,
            'habit_schedule_id' => $otherSchedule->habit_schedule_id,
            'occurrence_date' => '2026-03-10',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.occurrences', [
                'start' => '2026-03-01',
                'end' => '2026-03-31',
            ]));

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.habit_id', $myHabit->habit_id);
    }

    public function test_resource_includes_habit_data(): void
    {
        $habit = Habit::withoutEvents(fn () => Habit::factory()->for($this->user)->create([
            'name' => 'Meditación',
            'color' => '#8b5cf6',
        ]));

        $schedule = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->create([
            'habit_id' => $habit->habit_id,
            'start_time' => '07:00',
            'end_time' => '07:20',
        ]));

        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => '2026-03-10',
            'start_time' => '07:00',
            'end_time' => '07:20',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.occurrences', [
                'start' => '2026-03-01',
                'end' => '2026-03-31',
            ]));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                [
                    'habit_occurrence_id',
                    'habit_id',
                    'habit_schedule_id',
                    'occurrence_date',
                    'start_time',
                    'end_time',
                    'habit' => [
                        'habit_id',
                        'name',
                        'color',
                        'habit_nature',
                        'habit_nature_label',
                        'desire_type',
                        'desire_type_label',
                        'is_active',
                    ],
                ],
            ],
        ]);

        $response->assertJsonPath('data.0.habit.name', 'Meditación');
        $response->assertJsonPath('data.0.habit.color', '#8b5cf6');
        $response->assertJsonPath('data.0.occurrence_date', '2026-03-10');
    }

    public function test_occurrences_ordered_by_date_and_time(): void
    {
        $habit = Habit::withoutEvents(fn () => Habit::factory()->for($this->user)->create());

        $schedule = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->create([
            'habit_id' => $habit->habit_id,
        ]));

        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => '2026-03-15',
            'start_time' => '09:00',
            'end_time' => '09:30',
        ]);

        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => '2026-03-10',
            'start_time' => '18:00',
            'end_time' => '18:30',
        ]);

        HabitOccurrence::factory()->create([
            'habit_id' => $habit->habit_id,
            'habit_schedule_id' => $schedule->habit_schedule_id,
            'occurrence_date' => '2026-03-10',
            'start_time' => '07:00',
            'end_time' => '07:30',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.occurrences', [
                'start' => '2026-03-01',
                'end' => '2026-03-31',
            ]));

        $response->assertOk();
        $dates = collect($response->json('data'))->pluck('occurrence_date')->toArray();
        $times = collect($response->json('data'))->pluck('start_time')->toArray();

        $this->assertEquals(['2026-03-10', '2026-03-10', '2026-03-15'], $dates);
        $this->assertEquals(['07:00', '18:00', '09:00'], $times);
    }
}
