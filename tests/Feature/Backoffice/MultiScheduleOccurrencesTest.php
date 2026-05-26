<?php

namespace Tests\Feature\Backoffice;

use App\Models\Habit;
use App\Models\HabitOccurrence;
use App\Models\HabitSchedule;
use App\Models\User;
use Core\BoundedContext\HabitOccurrences\Application\Actions\ExtendOccurrencesForHabit;
use Core\BoundedContext\HabitOccurrences\Application\Actions\RebuildOccurrencesForHabit;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitId;
use Core\BoundedContext\HabitSchedules\Application\HabitScheduleReader;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression for the reported bug: "le pedí el gimnasio Lun/Mar/Jue 19-21 y
 * Mié/Vie 17-19, me dice que lo creó, pero no lo veo en el calendario".
 *
 * A habit can have more than one active schedule. The reader collapsed them
 * to one per habit_id, so the occurrence builders only ever materialized the
 * last schedule. These tests would FAIL on the buggy code (only one schedule
 * produced occurrences) and pass once every active schedule is honored.
 */
class MultiScheduleOccurrencesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Gym habit exactly as reported: Mon/Tue/Thu 19:00-21:00 and Wed/Fri 17:00-19:00.
     *
     * @return array{0: Habit, 1: HabitSchedule, 2: HabitSchedule}
     */
    private function gymHabitWithTwoSchedules(?User $user = null): array
    {
        $habit = Habit::withoutEvents(fn () => $user !== null
            ? Habit::factory()->build()->for($user)->create()
            : Habit::factory()->build()->create());

        // Mon (1), Tue (2), Thu (4) — 19:00 a 21:00
        $monTueThu = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->weekly([1, 2, 4])->create([
            'habit_id' => $habit->habit_id,
            'start_time' => '19:00',
            'end_time' => '21:00',
            'starts_from' => '2026-05-26',
            'is_active' => true,
        ]));

        // Wed (3), Fri (5) — 17:00 a 19:00
        $wedFri = HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->weekly([3, 5])->create([
            'habit_id' => $habit->habit_id,
            'start_time' => '17:00',
            'end_time' => '19:00',
            'starts_from' => '2026-05-26',
            'is_active' => true,
        ]));

        return [$habit, $monTueThu, $wedFri];
    }

    // -----------------------------------------------------------------------
    // Reader (the piece with the bug)
    // -----------------------------------------------------------------------

    public function test_reader_returns_all_active_schedules_grouped_by_habit(): void
    {
        [$habit] = $this->gymHabitWithTwoSchedules();

        $result = app(HabitScheduleReader::class)->findAllActiveByHabitIds([$habit->habit_id]);

        $this->assertCount(2, $result[$habit->habit_id]);
    }

    public function test_reader_excludes_inactive_schedules(): void
    {
        [$habit] = $this->gymHabitWithTwoSchedules();

        HabitSchedule::withoutEvents(fn () => HabitSchedule::factory()->weekly([6])->create([
            'habit_id' => $habit->habit_id,
            'is_active' => false,
        ]));

        $this->assertCount(2, app(HabitScheduleReader::class)->findAllActiveByHabitIds([$habit->habit_id])[$habit->habit_id]);
    }

    /**
     * Guard: the single-snapshot reader used by the backoffice list was left
     * untouched on purpose (the list view is not changing yet).
     */
    public function test_legacy_single_reader_still_returns_one_snapshot_per_habit(): void
    {
        [$habit] = $this->gymHabitWithTwoSchedules();

        $result = app(HabitScheduleReader::class)->findActiveByHabitIds([$habit->habit_id]);

        $this->assertInstanceOf(
            \Core\BoundedContext\HabitSchedules\Application\ReadModels\HabitScheduleSnapshot::class,
            $result[$habit->habit_id],
        );
    }

    // -----------------------------------------------------------------------
    // Rebuild
    // -----------------------------------------------------------------------

    public function test_rebuild_generates_occurrences_for_every_active_schedule(): void
    {
        [$habit, $monTueThu, $wedFri] = $this->gymHabitWithTwoSchedules();

        app(RebuildOccurrencesForHabit::class)(HabitId::from($habit->habit_id), new DateTimeImmutable('2026-05-26'));

        $this->assertGreaterThan(
            0,
            HabitOccurrence::where('habit_schedule_id', $monTueThu->habit_schedule_id)->count(),
            'The Mon/Tue/Thu schedule must produce occurrences (it produced none with the bug).'
        );
        $this->assertGreaterThan(
            0,
            HabitOccurrence::where('habit_schedule_id', $wedFri->habit_schedule_id)->count(),
            'The Wed/Fri schedule must produce occurrences.'
        );
    }

    public function test_rebuild_assigns_correct_weekdays_and_times_per_schedule(): void
    {
        [$habit, $monTueThu, $wedFri] = $this->gymHabitWithTwoSchedules();

        app(RebuildOccurrencesForHabit::class)(HabitId::from($habit->habit_id), new DateTimeImmutable('2026-05-26'));

        // Carbon dayOfWeek: 0=Sun .. 6=Sat
        foreach (HabitOccurrence::where('habit_schedule_id', $monTueThu->habit_schedule_id)->get() as $occ) {
            $this->assertContains($occ->occurrence_date->dayOfWeek, [1, 2, 4]);
            $this->assertSame('19:00', substr((string) $occ->start_time, 0, 5));
            $this->assertSame('21:00', substr((string) $occ->end_time, 0, 5));
        }

        foreach (HabitOccurrence::where('habit_schedule_id', $wedFri->habit_schedule_id)->get() as $occ) {
            $this->assertContains($occ->occurrence_date->dayOfWeek, [3, 5]);
            $this->assertSame('17:00', substr((string) $occ->start_time, 0, 5));
            $this->assertSame('19:00', substr((string) $occ->end_time, 0, 5));
        }
    }

    // -----------------------------------------------------------------------
    // Extend (12-month cron)
    // -----------------------------------------------------------------------

    public function test_extend_generates_occurrences_for_every_active_schedule(): void
    {
        [$habit, $monTueThu, $wedFri] = $this->gymHabitWithTwoSchedules();

        app(ExtendOccurrencesForHabit::class)(HabitId::from($habit->habit_id), new DateTimeImmutable('2026-05-26'));

        $this->assertGreaterThan(0, HabitOccurrence::where('habit_schedule_id', $monTueThu->habit_schedule_id)->count());
        $this->assertGreaterThan(0, HabitOccurrence::where('habit_schedule_id', $wedFri->habit_schedule_id)->count());
    }

    // -----------------------------------------------------------------------
    // End-to-end: the reported symptom (the calendar)
    // -----------------------------------------------------------------------

    public function test_both_schedules_are_visible_in_the_calendar_endpoint(): void
    {
        $user = User::factory()->create();
        [$habit] = $this->gymHabitWithTwoSchedules($user);

        app(RebuildOccurrencesForHabit::class)(HabitId::from($habit->habit_id), new DateTimeImmutable('2026-05-26'));

        $response = $this->actingAs($user)->getJson(route('backoffice.calendar.occurrences', [
            'start' => '2026-05-26',
            'end' => '2026-06-08',
        ]));

        $response->assertOk();

        $startTimes = collect($response->json('data'))->pluck('start_time')->unique()->values();

        $this->assertTrue($startTimes->contains('19:00'), 'Gym Mon/Tue/Thu 19:00 must appear in the calendar.');
        $this->assertTrue($startTimes->contains('17:00'), 'Gym Wed/Fri 17:00 must appear in the calendar.');
    }
}
