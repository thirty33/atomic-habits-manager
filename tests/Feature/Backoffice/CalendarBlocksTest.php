<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice;

use App\Enums\ReportEntryStatus;
use App\Models\DailyReport;
use App\Models\DailyReportEntry;
use App\Models\Habit;
use App\Models\HabitOccurrence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarBlocksTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    private function makeHabit(array $attributes = []): Habit
    {
        return Habit::withoutEvents(fn () => Habit::factory()->for($this->user)->create($attributes));
    }

    private function makeOccurrence(Habit $habit, string $date, array $attributes = []): HabitOccurrence
    {
        return HabitOccurrence::factory()->create(array_merge([
            'habit_id' => $habit->habit_id,
            'occurrence_date' => $date,
        ], $attributes));
    }

    private function attachStatus(HabitOccurrence $occurrence, ReportEntryStatus $status): void
    {
        $report = DailyReport::factory()->create([
            'user_id' => $this->user->user_id,
            'report_date' => $occurrence->occurrence_date->toDateString(),
        ]);

        DailyReportEntry::factory()->create([
            'daily_report_id' => $report->getKey(),
            'habit_occurrence_id' => $occurrence->habit_occurrence_id,
            'habit_id' => $occurrence->habit_id,
            'status' => $status->value,
        ]);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson(route('backoffice.calendar.blocks', [
            'start' => '2026-05-01',
            'end' => '2026-05-31',
        ]))->assertUnauthorized();
    }

    public function test_requires_start_and_end(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.blocks'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start', 'end']);
    }

    public function test_end_cannot_be_before_start(): void
    {
        $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.blocks', [
                'start' => '2026-05-31',
                'end' => '2026-05-01',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['end']);
    }

    public function test_a_single_day_range_is_allowed(): void
    {
        $habit = $this->makeHabit();
        $this->makeOccurrence($habit, '2026-05-12');

        $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.blocks', [
                'start' => '2026-05-12',
                'end' => '2026-05-12',
            ]))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_returns_blocks_within_range_only(): void
    {
        $habit = $this->makeHabit();
        $this->makeOccurrence($habit, '2026-05-10');
        $this->makeOccurrence($habit, '2026-05-20');
        $this->makeOccurrence($habit, '2026-06-15');

        $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.blocks', [
                'start' => '2026-05-01',
                'end' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_does_not_return_other_users_blocks(): void
    {
        $otherUser = User::factory()->create();
        $otherHabit = Habit::withoutEvents(fn () => Habit::factory()->for($otherUser)->create());
        $this->makeOccurrence($otherHabit, '2026-05-12');

        $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.blocks', [
                'start' => '2026-05-01',
                'end' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_maps_report_entry_status_to_calendar_status(): void
    {
        $habit = $this->makeHabit();

        $cases = [
            '2026-05-10' => [ReportEntryStatus::Completed, 'done'],
            '2026-05-11' => [ReportEntryStatus::Partial, 'partial'],
            '2026-05-12' => [ReportEntryStatus::NotCompleted, 'missed'],
            '2026-05-13' => [ReportEntryStatus::Skipped, 'skipped'],
            '2026-05-14' => [ReportEntryStatus::Pending, 'pending'],
        ];

        $expected = [];
        foreach ($cases as $date => [$entryStatus, $calendarStatus]) {
            $occurrence = $this->makeOccurrence($habit, $date);
            $this->attachStatus($occurrence, $entryStatus);
            $expected[$occurrence->habit_occurrence_id] = $calendarStatus;
        }

        // Occurrence with no report entry at all -> pending.
        $bare = $this->makeOccurrence($habit, '2026-05-15');
        $expected[$bare->habit_occurrence_id] = 'pending';

        $data = $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.blocks', [
                'start' => '2026-05-01',
                'end' => '2026-05-31',
            ]))
            ->assertOk()
            ->json('data');

        $actual = [];
        foreach ($data as $block) {
            $actual[$block['habit_occurrence_id']] = $block['status'];
        }

        $this->assertSame($expected, $actual);
    }

    public function test_preserves_end_date_for_overnight_blocks(): void
    {
        $habit = $this->makeHabit();
        $this->makeOccurrence($habit, '2026-05-12', [
            'end_date' => '2026-05-13',
            'start_time' => '23:40',
            'end_time' => '07:00',
        ]);

        $block = $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.blocks', [
                'start' => '2026-05-01',
                'end' => '2026-05-31',
            ]))
            ->assertOk()
            ->json('data.0');

        $this->assertSame('2026-05-12', $block['occurrence_date']);
        $this->assertSame('2026-05-13', $block['end_date']);
        $this->assertSame('23:40', $block['start_time']);
        $this->assertSame('07:00', $block['end_time']);
    }

    public function test_orders_by_date_then_start_time(): void
    {
        $habit = $this->makeHabit();
        $this->makeOccurrence($habit, '2026-05-12', ['start_time' => '09:00', 'end_time' => '09:30']);
        $this->makeOccurrence($habit, '2026-05-12', ['start_time' => '06:30', 'end_time' => '07:00']);
        $this->makeOccurrence($habit, '2026-05-11', ['start_time' => '20:00', 'end_time' => '20:30']);

        $data = $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.blocks', [
                'start' => '2026-05-01',
                'end' => '2026-05-31',
            ]))
            ->assertOk()
            ->json('data');

        $this->assertSame(['2026-05-11', '2026-05-12', '2026-05-12'], array_column($data, 'occurrence_date'));
        $this->assertSame(['20:00', '06:30', '09:00'], array_column($data, 'start_time'));
    }

    public function test_block_json_shape(): void
    {
        $habit = $this->makeHabit(['name' => 'Meditación', 'color' => '#8b5cf6']);
        $occurrence = $this->makeOccurrence($habit, '2026-05-12', ['start_time' => '07:00', 'end_time' => '07:20']);
        $this->attachStatus($occurrence, ReportEntryStatus::Completed);

        $this->actingAs($this->user)
            ->getJson(route('backoffice.calendar.blocks', [
                'start' => '2026-05-01',
                'end' => '2026-05-31',
            ]))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [[
                    'habit_occurrence_id', 'habit_id', 'habit_schedule_id',
                    'occurrence_date', 'end_date', 'start_time', 'end_time',
                    'status', 'status_label',
                    'habit' => ['habit_id', 'name', 'color', 'accent', 'habit_nature', 'habit_nature_label', 'desire_type', 'desire_type_label', 'is_active'],
                ]],
            ])
            ->assertJsonPath('data.0.status', 'done')
            ->assertJsonPath('data.0.status_label', 'Completado')
            ->assertJsonPath('data.0.habit.name', 'Meditación');
    }
}
