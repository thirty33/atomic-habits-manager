<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\TestData\HabitTestData;
use Tests\DuskTestCase;

class HabitCreationTest extends DuskTestCase
{
    use DatabaseTruncation;

    private function setNativeInputValue(Browser $browser, string $name, string $value): void
    {
        $browser->script("
            const input = document.querySelector('form [name=\"{$name}\"]');
            const nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value').set;
            nativeInputValueSetter.call(input, '{$value}');
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        ");
    }

    private function fillStep1(Browser $browser, array $step1): void
    {
        $browser
            ->waitForText('Información del hábito')
            ->within('form', function (Browser $form) use ($step1) {
                $form
                    ->type('name', $step1['name']);

                if ($step1['description']) {
                    $form->type('description', $step1['description']);
                }

                $form
                    ->select('habit_nature', $step1['habit_nature'])
                    ->select('desire_type', $step1['desire_type']);

                if ($step1['implementation_intention']) {
                    $form->type('implementation_intention', $step1['implementation_intention']);
                }

                if ($step1['location']) {
                    $form->type('location', $step1['location']);
                }

                if ($step1['cue']) {
                    $form->type('cue', $step1['cue']);
                }

                if ($step1['reframe']) {
                    $form->type('reframe', $step1['reframe']);
                }

                if ($step1['is_active'] === false) {
                    $form->uncheck('is_active');
                }
            })
            ->press('Siguiente');
    }

    private function fillStep2Schedule(Browser $browser, array $step2): void
    {
        $browser
            ->waitForText('Programar hábito')
            ->within('form', function (Browser $form) use ($step2) {
                $form->select('recurrence_type', $step2['recurrence_type']);
            });

        $this->setNativeInputValue($browser, 'start_time', $step2['start_time']);
        $this->setNativeInputValue($browser, 'end_time', $step2['end_time']);

        if (isset($step2['starts_from'])) {
            $this->setNativeInputValue($browser, 'starts_from', $step2['starts_from']);
        }

        if (isset($step2['ends_at']) && $step2['ends_at']) {
            $this->setNativeInputValue($browser, 'ends_at', $step2['ends_at']);
        }

        if (isset($step2['specific_date'])) {
            $this->setNativeInputValue($browser, 'specific_date', $step2['specific_date']);
        }

        if (isset($step2['interval_days'])) {
            $browser->within('form', function (Browser $form) use ($step2) {
                $form->type('interval_days', (string) $step2['interval_days']);
            });
        }

        if (isset($step2['days_of_week_labels'])) {
            foreach ($step2['days_of_week_labels'] as $dayLabel) {
                $browser->press($dayLabel);
            }
        }

        $browser->press('Guardar programación');
        $browser->waitForText('Programación guardada');
    }

    private function assertHabitInTable(Browser $browser, array $step1): void
    {
        $browser
            ->waitForText($step1['name'])
            ->assertSee($step1['habit_nature_label'])
            ->assertSee($step1['desire_type_label']);
    }

    private function createHabitWithSchedule(string $dataKey): void
    {
        $data = HabitTestData::habits()[$dataKey];
        $step1 = $data['step1'];
        $step2 = $data['step2'];

        $this->actingAsAdmin(function (Browser $browser) use ($step1, $step2) {
            $browser
                ->visitRoute('backoffice.habits.index')
                ->waitForText('Crear habito')
                ->press('Crear habito');

            $this->fillStep1($browser, $step1);

            if ($step2 === null) {
                $browser
                    ->waitForText('Programar hábito')
                    ->press('Omitir por ahora');
            } else {
                $this->fillStep2Schedule($browser, $step2);
            }

            $this->assertHabitInTable($browser, $step1);
        });
    }

    public function testCreateHabitBuildNeedWithDailySchedule(): void
    {
        $this->createHabitWithSchedule('build_need_daily');
    }

    public function testCreateHabitBuildWantWithWeeklySchedule(): void
    {
        $this->createHabitWithSchedule('build_want_weekly');
    }

    public function testCreateHabitBuildNeutralWithEveryNDaysSchedule(): void
    {
        $this->createHabitWithSchedule('build_neutral_every_n_days');
    }

    public function testCreateHabitBreakNeedWithOneTimeSchedule(): void
    {
        $this->createHabitWithSchedule('break_need_none');
    }

    public function testCreateHabitBreakWantSkipSchedule(): void
    {
        $this->createHabitWithSchedule('break_want_skip');
    }

    public function testCreateHabitBreakNeutralDailyInactive(): void
    {
        $this->createHabitWithSchedule('break_neutral_daily_inactive');
    }
}