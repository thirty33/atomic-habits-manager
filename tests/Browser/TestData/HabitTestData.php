<?php

namespace Tests\Browser\TestData;

class HabitTestData
{
    public static function habits(): array
    {
        return [
            'build_need_daily' => [
                'step1' => [
                    'name' => 'Leer antes de dormir',
                    'description' => 'Leer al menos 30 minutos cada dia',
                    'habit_nature' => 'build',
                    'habit_nature_label' => 'Quiero adoptar un buen habito',
                    'desire_type' => 'need',
                    'desire_type_label' => 'Es algo que necesito hacer',
                    'implementation_intention' => 'Despues de cenar, voy a leer en el sofa',
                    'location' => 'Sala de estar',
                    'cue' => 'Terminar de cenar',
                    'reframe' => 'Voy a expandir mi conocimiento',
                    'is_active' => true,
                ],
                'step2' => [
                    'recurrence_type' => 'daily',
                    'recurrence_type_label' => 'Todos los días',
                    'start_time' => '08:00',
                    'end_time' => '08:30',
                    'starts_from' => '2026-02-05',
                    'ends_at' => null,
                ],
            ],
            'build_want_weekly' => [
                'step1' => [
                    'name' => 'Hacer ejercicio',
                    'description' => 'Entrenar en el gimnasio',
                    'habit_nature' => 'build',
                    'habit_nature_label' => 'Quiero adoptar un buen habito',
                    'desire_type' => 'want',
                    'desire_type_label' => 'Es algo que quiero hacer',
                    'implementation_intention' => 'Al despertar, voy al gimnasio',
                    'location' => 'Gimnasio',
                    'cue' => 'Alarma a las 6am',
                    'reframe' => 'Voy a mejorar mi energia y salud',
                    'is_active' => true,
                ],
                'step2' => [
                    'recurrence_type' => 'weekly',
                    'recurrence_type_label' => 'Algunos días de la semana',
                    'start_time' => '06:30',
                    'end_time' => '07:30',
                    'days_of_week' => [1, 3, 5],
                    'days_of_week_labels' => ['Lun', 'Mié', 'Vie'],
                    'starts_from' => '2026-02-05',
                    'ends_at' => null,
                ],
            ],
            'build_neutral_every_n_days' => [
                'step1' => [
                    'name' => 'Meditar',
                    'description' => null,
                    'habit_nature' => 'build',
                    'habit_nature_label' => 'Quiero adoptar un buen habito',
                    'desire_type' => 'neutral',
                    'desire_type_label' => 'No estoy seguro aun',
                    'implementation_intention' => null,
                    'location' => 'Habitacion',
                    'cue' => null,
                    'reframe' => null,
                    'is_active' => true,
                ],
                'step2' => [
                    'recurrence_type' => 'every_n_days',
                    'recurrence_type_label' => 'Cada ciertos días',
                    'start_time' => '07:00',
                    'end_time' => '07:20',
                    'interval_days' => 3,
                    'starts_from' => '2026-02-05',
                    'ends_at' => '2026-06-30',
                ],
            ],
            'break_need_none' => [
                'step1' => [
                    'name' => 'Dejar de fumar',
                    'description' => 'Eliminar el habito de fumar',
                    'habit_nature' => 'break',
                    'habit_nature_label' => 'Quiero dejar un mal habito',
                    'desire_type' => 'need',
                    'desire_type_label' => 'Es algo que necesito hacer',
                    'implementation_intention' => 'Cuando sienta ganas, voy a masticar chicle',
                    'location' => null,
                    'cue' => 'Ganas de fumar',
                    'reframe' => 'Estoy protegiendo mis pulmones',
                    'is_active' => true,
                ],
                'step2' => [
                    'recurrence_type' => 'none',
                    'recurrence_type_label' => 'Solo una vez',
                    'start_time' => '10:00',
                    'end_time' => '10:30',
                    'specific_date' => '2026-03-01',
                ],
            ],
            'break_want_skip' => [
                'step1' => [
                    'name' => 'No revisar redes sociales',
                    'description' => 'Evitar redes sociales antes de dormir',
                    'habit_nature' => 'break',
                    'habit_nature_label' => 'Quiero dejar un mal habito',
                    'desire_type' => 'want',
                    'desire_type_label' => 'Es algo que quiero hacer',
                    'implementation_intention' => 'A las 9pm, guardar el telefono en el cajon',
                    'location' => 'Habitacion',
                    'cue' => 'Alarma a las 9pm',
                    'reframe' => 'Voy a dormir mejor y descansar mas',
                    'is_active' => true,
                ],
                'step2' => null,
            ],
            'break_neutral_daily_inactive' => [
                'step1' => [
                    'name' => 'Dejar comida chatarra',
                    'description' => 'Reducir consumo de comida procesada',
                    'habit_nature' => 'break',
                    'habit_nature_label' => 'Quiero dejar un mal habito',
                    'desire_type' => 'neutral',
                    'desire_type_label' => 'No estoy seguro aun',
                    'implementation_intention' => null,
                    'location' => 'Casa',
                    'cue' => null,
                    'reframe' => null,
                    'is_active' => false,
                ],
                'step2' => [
                    'recurrence_type' => 'daily',
                    'recurrence_type_label' => 'Todos los días',
                    'start_time' => '12:00',
                    'end_time' => '13:00',
                    'starts_from' => '2026-02-05',
                    'ends_at' => null,
                ],
            ],
        ];
    }
}