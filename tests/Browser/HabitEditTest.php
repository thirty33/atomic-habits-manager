<?php

namespace Tests\Browser;

use App\Models\Habit;
use App\Models\HabitSchedule;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\TestData\HabitTestData;
use Tests\DuskTestCase;

class HabitEditTest extends DuskTestCase
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

    private function createHabitDirectly(int $userId, string $dataKey): Habit
    {
        $data = HabitTestData::habits()[$dataKey];
        $step1 = $data['step1'];
        $step2 = $data['step2'];

        $habit = Habit::create([
            'user_id' => $userId,
            'name' => $step1['name'],
            'description' => $step1['description'],
            'habit_nature' => $step1['habit_nature'],
            'desire_type' => $step1['desire_type'],
            'implementation_intention' => $step1['implementation_intention'],
            'location' => $step1['location'],
            'cue' => $step1['cue'],
            'reframe' => $step1['reframe'],
            'is_active' => $step1['is_active'],
        ]);

        if ($step2) {
            HabitSchedule::create([
                'habit_id' => $habit->habit_id,
                'recurrence_type' => $step2['recurrence_type'],
                'start_time' => $step2['start_time'],
                'end_time' => $step2['end_time'],
                'days_of_week' => $step2['days_of_week'] ?? null,
                'interval_days' => $step2['interval_days'] ?? null,
                'specific_date' => $step2['specific_date'] ?? null,
                'starts_from' => $step2['starts_from'] ?? null,
                'ends_at' => $step2['ends_at'] ?? null,
                'is_active' => true,
            ]);
        }

        return $habit;
    }

    private function clickEditButton(Browser $browser, string $habitName): void
    {
        $escapedName = addslashes($habitName);
        $browser->script("
            const rows = document.querySelectorAll('table tbody tr');
            for (const row of rows) {
                if (row.querySelector('td')?.textContent?.trim() === '{$escapedName}') {
                    const editBtn = Array.from(row.querySelectorAll('button')).find(b => b.textContent.trim() === 'Editar');
                    if (editBtn) editBtn.click();
                    break;
                }
            }
        ");
    }

    public function test_edit_habit_name_and_update_schedule(): void
    {
        $this->actingAsAdmin(function (Browser $browser, $user) {
            $this->createHabitDirectly($user->user_id, 'build_need_daily');

            $browser
                ->visitRoute('backoffice.habits.index')
                ->waitForText('Leer antes de dormir');

            $this->clickEditButton($browser, 'Leer antes de dormir');

            // Step 1: Edit habit name
            $browser
                ->waitForText('Información del hábito')
                ->within('form', function (Browser $form) {
                    $form->clear('name')->type('name', 'Leer antes de dormir (editado)');
                })
                ->press('Siguiente');

            // Step 2: Update schedule times
            $browser->waitForText('Programar hábito');

            $this->setNativeInputValue($browser, 'start_time', '09:00');
            $this->setNativeInputValue($browser, 'end_time', '09:30');

            $browser
                ->press('Guardar programación')
                ->waitForText('Programación actualizada')
                ->waitForText('Leer antes de dormir (editado)');
        });
    }

    public function test_edit_habit_and_skip_schedule_step(): void
    {
        $this->actingAsAdmin(function (Browser $browser, $user) {
            $this->createHabitDirectly($user->user_id, 'build_neutral_every_n_days');

            $browser
                ->visitRoute('backoffice.habits.index')
                ->waitForText('Meditar');

            $this->clickEditButton($browser, 'Meditar');

            // Step 1: Edit habit name
            $browser
                ->waitForText('Información del hábito')
                ->within('form', function (Browser $form) {
                    $form->clear('name')->type('name', 'Meditar (editado)');
                })
                ->press('Siguiente');

            // Step 2: Skip
            $browser
                ->waitForText('Programar hábito')
                ->press('Omitir');

            // Verify table updated with new name
            $browser->waitForText('Meditar (editado)');
        });
    }

    public function test_edit_habit_and_change_schedule_frequency(): void
    {
        $this->actingAsAdmin(function (Browser $browser, $user) {
            $this->createHabitDirectly($user->user_id, 'break_neutral_daily_inactive');

            $browser
                ->visitRoute('backoffice.habits.index')
                ->waitForText('Dejar comida chatarra');

            $this->clickEditButton($browser, 'Dejar comida chatarra');

            // Step 1: Just proceed
            $browser
                ->waitForText('Información del hábito')
                ->press('Siguiente');

            // Step 2: Change from daily to weekly
            $browser
                ->waitForText('Programar hábito')
                ->within('form', function (Browser $form) {
                    $form->select('recurrence_type', 'weekly');
                });

            $this->setNativeInputValue($browser, 'start_time', '12:00');
            $this->setNativeInputValue($browser, 'end_time', '13:00');

            $browser
                ->press('Lun')
                ->press('Mié')
                ->press('Vie');

            $this->setNativeInputValue($browser, 'starts_from', '2026-02-05');

            $browser
                ->press('Guardar programación')
                ->waitForText('Programación actualizada')
                ->waitForText('Dejar comida chatarra');
        });
    }

    public function test_edit_habit_without_schedule_and_create_schedule(): void
    {
        $this->actingAsAdmin(function (Browser $browser, $user) {
            $this->createHabitDirectly($user->user_id, 'break_want_skip');

            $browser
                ->visitRoute('backoffice.habits.index')
                ->waitForText('No revisar redes sociales');

            $this->clickEditButton($browser, 'No revisar redes sociales');

            // Step 1: Just proceed
            $browser
                ->waitForText('Información del hábito')
                ->press('Siguiente');

            // Step 2: Create new schedule (no existing schedule)
            $browser
                ->waitForText('Programar hábito')
                ->within('form', function (Browser $form) {
                    $form->select('recurrence_type', 'daily');
                });

            $this->setNativeInputValue($browser, 'start_time', '21:00');
            $this->setNativeInputValue($browser, 'end_time', '21:30');
            $this->setNativeInputValue($browser, 'starts_from', '2026-02-06');

            $browser
                ->press('Guardar programación')
                ->waitForText('Programación guardada')
                ->waitForText('No revisar redes sociales');
        });
    }
}
