<script>
export default {
    name: 'CalendarPage',
}
</script>

<script setup>
import { ref } from 'vue';
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import DataProvider from '@/providers/DataProvider.js';
import useDataProvider from '@/composables/useDataProvider.js';

defineProps({
    jsonUrl: {
        type: String,
        required: true,
    },
});

const { dataProviderKey } = useDataProvider();

// ---------------------------------------------------------------------------
// Hardcoded occurrences — same shape as HabitOccurrenceResource will return.
// Replace apiResponse.data with the real API response when backend is ready.
// ---------------------------------------------------------------------------

const TODAY = new Date().toISOString().slice(0, 10);

function d(offset) {
    const dt = new Date(TODAY);
    dt.setDate(dt.getDate() + offset);
    return dt.toISOString().slice(0, 10);
}

const apiResponse = {
    data: [
        // Meditación — daily, build, color propio
        { habit_occurrence_id: 1,  habit_id: 1, habit_schedule_id: 10, occurrence_date: d(-2), start_time: '07:00', end_time: '07:20', status: 'completed',     completed_at: `${d(-2)}T07:18:00Z`, notes: 'Muy bien hoy', update_action: { url: '/backoffice/occurrences/1',  method: 'patch' }, habit: { habit_id: 1, name: 'Meditación',           color: '#8b5cf6', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'want', desire_type_label: 'Quiero hacerlo',   is_active: true } },
        { habit_occurrence_id: 2,  habit_id: 1, habit_schedule_id: 10, occurrence_date: d(-1), start_time: '07:00', end_time: '07:20', status: 'skipped',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/2',  method: 'patch' }, habit: { habit_id: 1, name: 'Meditación',           color: '#8b5cf6', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'want', desire_type_label: 'Quiero hacerlo',   is_active: true } },
        { habit_occurrence_id: 3,  habit_id: 1, habit_schedule_id: 10, occurrence_date: d( 0), start_time: '07:00', end_time: '07:20', status: 'pending',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/3',  method: 'patch' }, habit: { habit_id: 1, name: 'Meditación',           color: '#8b5cf6', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'want', desire_type_label: 'Quiero hacerlo',   is_active: true } },
        { habit_occurrence_id: 4,  habit_id: 1, habit_schedule_id: 10, occurrence_date: d( 1), start_time: '07:00', end_time: '07:20', status: 'pending',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/4',  method: 'patch' }, habit: { habit_id: 1, name: 'Meditación',           color: '#8b5cf6', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'want', desire_type_label: 'Quiero hacerlo',   is_active: true } },
        // Ejercicio — weekly, build, sin color
        { habit_occurrence_id: 5,  habit_id: 2, habit_schedule_id: 11, occurrence_date: d(-1), start_time: '06:00', end_time: '07:00', status: 'completed',     completed_at: `${d(-1)}T07:01:00Z`, notes: null,          update_action: { url: '/backoffice/occurrences/5',  method: 'patch' }, habit: { habit_id: 2, name: 'Ejercicio',             color: null,      habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'need', desire_type_label: 'Necesito hacerlo', is_active: true } },
        { habit_occurrence_id: 6,  habit_id: 2, habit_schedule_id: 11, occurrence_date: d( 3), start_time: '06:00', end_time: '07:00', status: 'pending',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/6',  method: 'patch' }, habit: { habit_id: 2, name: 'Ejercicio',             color: null,      habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'need', desire_type_label: 'Necesito hacerlo', is_active: true } },
        // Revisar redes — daily, break
        { habit_occurrence_id: 7,  habit_id: 3, habit_schedule_id: 12, occurrence_date: d(-1), start_time: '22:00', end_time: '22:30', status: 'not_completed', completed_at: null,               notes: 'No pude',      update_action: { url: '/backoffice/occurrences/7',  method: 'patch' }, habit: { habit_id: 3, name: 'Revisar redes sociales', color: null,      habit_nature: 'break', habit_nature_label: 'Romper hábito',    desire_type: 'need', desire_type_label: 'Necesito hacerlo', is_active: true } },
        { habit_occurrence_id: 8,  habit_id: 3, habit_schedule_id: 12, occurrence_date: d( 0), start_time: '22:00', end_time: '22:30', status: 'pending',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/8',  method: 'patch' }, habit: { habit_id: 3, name: 'Revisar redes sociales', color: null,      habit_nature: 'break', habit_nature_label: 'Romper hábito',    desire_type: 'need', desire_type_label: 'Necesito hacerlo', is_active: true } },
        // Lectura — every_n_days:3, build, color ámbar
        { habit_occurrence_id: 9,  habit_id: 4, habit_schedule_id: 13, occurrence_date: d( 0), start_time: '21:00', end_time: '21:45', status: 'pending',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/9',  method: 'patch' }, habit: { habit_id: 4, name: 'Lectura',               color: '#f59e0b', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'want', desire_type_label: 'Quiero hacerlo',   is_active: true } },
        { habit_occurrence_id: 10, habit_id: 4, habit_schedule_id: 13, occurrence_date: d( 3), start_time: '21:00', end_time: '21:45', status: 'pending',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/10', method: 'patch' }, habit: { habit_id: 4, name: 'Lectura',               color: '#f59e0b', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'want', desire_type_label: 'Quiero hacerlo',   is_active: true } },
        // Cita médica — recurrence_type:none (actividad eventual)
        { habit_occurrence_id: 11, habit_id: 5, habit_schedule_id: 14, occurrence_date: d( 2), start_time: '10:00', end_time: '11:00', status: 'pending',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/11', method: 'patch' }, habit: { habit_id: 5, name: 'Cita médica',           color: '#64748b', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'need', desire_type_label: 'Necesito hacerlo', is_active: true } },
        // Dieta saludable — daily, partial
        { habit_occurrence_id: 12, habit_id: 6, habit_schedule_id: 15, occurrence_date: d(-1), start_time: '13:00', end_time: '13:30', status: 'partial',       completed_at: null,               notes: 'No cené bien', update_action: { url: '/backoffice/occurrences/12', method: 'patch' }, habit: { habit_id: 6, name: 'Dieta saludable',      color: '#22c55e', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'need', desire_type_label: 'Necesito hacerlo', is_active: true } },
        { habit_occurrence_id: 13, habit_id: 6, habit_schedule_id: 15, occurrence_date: d( 0), start_time: '13:00', end_time: '13:30', status: 'pending',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/13', method: 'patch' }, habit: { habit_id: 6, name: 'Dieta saludable',      color: '#22c55e', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'need', desire_type_label: 'Necesito hacerlo', is_active: true } },
        // Journaling — daily, skipped
        { habit_occurrence_id: 14, habit_id: 7, habit_schedule_id: 16, occurrence_date: d(-2), start_time: '08:00', end_time: '08:15', status: 'skipped',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/14', method: 'patch' }, habit: { habit_id: 7, name: 'Journaling',            color: '#0ea5e9', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'want', desire_type_label: 'Quiero hacerlo',   is_active: true } },
        { habit_occurrence_id: 15, habit_id: 7, habit_schedule_id: 16, occurrence_date: d( 0), start_time: '08:00', end_time: '08:15', status: 'pending',       completed_at: null,               notes: null,           update_action: { url: '/backoffice/occurrences/15', method: 'patch' }, habit: { habit_id: 7, name: 'Journaling',            color: '#0ea5e9', habit_nature: 'build', habit_nature_label: 'Construir hábito', desire_type: 'want', desire_type_label: 'Quiero hacerlo',   is_active: true } },
    ],
};

// ---------------------------------------------------------------------------
// Display constants
// ---------------------------------------------------------------------------

const STATUS_LABEL = {
    completed:     '✓',
    pending:       '○',
    partial:       '◑',
    not_completed: '✗',
    skipped:       '—',
};

const STATUS_STYLES = {
    completed:     { opacity: '1',   textDecoration: 'none' },
    pending:       { opacity: '0.9', textDecoration: 'none' },
    partial:       { opacity: '0.9', textDecoration: 'none' },
    not_completed: { opacity: '0.5', textDecoration: 'line-through' },
    skipped:       { opacity: '0.4', textDecoration: 'line-through' },
};

const NATURE_DEFAULT_COLOR = {
    build: '#6366f1',
    break: '#ef4444',
};

// ---------------------------------------------------------------------------
// occurrenceToEvent — will live in a composable when connecting to the API
// ---------------------------------------------------------------------------

function occurrenceToEvent(occurrence) {
    const { habit } = occurrence;
    const color = habit.color ?? NATURE_DEFAULT_COLOR[habit.habit_nature];

    return {
        id:              String(occurrence.habit_occurrence_id),
        title:           habit.name,
        start:           `${occurrence.occurrence_date}T${occurrence.start_time}`,
        end:             `${occurrence.occurrence_date}T${occurrence.end_time}`,
        backgroundColor: color,
        borderColor:     color,
        textColor:       '#ffffff',
        extendedProps: {
            habit_occurrence_id: occurrence.habit_occurrence_id,
            status:              occurrence.status,
            completed_at:        occurrence.completed_at,
            notes:               occurrence.notes,
            update_action:       occurrence.update_action,
            habit_id:            habit.habit_id,
            habit_nature:        habit.habit_nature,
            habit_nature_label:  habit.habit_nature_label,
            desire_type_label:   habit.desire_type_label,
        },
    };
}

// ---------------------------------------------------------------------------
// Calendar state
// ---------------------------------------------------------------------------

const selectedOccurrence = ref(null);

const calendarOptions = ref({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView: 'timeGridWeek',
    locale: 'es',
    headerToolbar: {
        left:   'prev,next today',
        center: 'title',
        right:  'dayGridMonth,timeGridWeek,timeGridDay',
    },
    buttonText: {
        today: 'Hoy',
        month: 'Mes',
        week:  'Sem',
        day:   'Día',
        list:  'Lista',
    },
    allDayText: 'hr',
    slotMinTime: '05:00:00',
    slotMaxTime: '23:30:00',
    slotDuration: '00:30:00',
    height: 'auto',
    nowIndicator: true,
    events: apiResponse.data.map(occurrenceToEvent),
    // When connecting to the real API, replace `events` with a function:
    // events(fetchInfo, successCallback) {
    //     axios.get(jsonUrl, { params: { start: fetchInfo.startStr, end: fetchInfo.endStr } })
    //         .then(r => successCallback(r.data.data.map(occurrenceToEvent)));
    // }
    eventClick({ event }) {
        const ep = event.extendedProps;
        selectedOccurrence.value = {
            title:              event.title,
            start:              event.startStr,
            end:                event.endStr,
            status:             ep.status,
            completed_at:       ep.completed_at,
            notes:              ep.notes,
            habit_nature:       ep.habit_nature,
            habit_nature_label: ep.habit_nature_label,
            desire_type_label:  ep.desire_type_label,
        };
    },
});
</script>

<template>
    <DataProvider
        :provider-key="dataProviderKey"
        :url="jsonUrl"
        @refreshed="() => {}"
    >
        <template v-slot="{ data }">
            <div class="p-4 lg:p-6">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">{{ data?.page_title ?? 'Calendario' }}</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Seguimiento diario de tus hábitos</p>
                    </div>
                    <div class="hidden sm:flex items-center gap-3 text-xs text-gray-500">
                        <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-indigo-500"></span> Build</span>
                        <span class="flex items-center gap-1"><span class="inline-block w-2.5 h-2.5 rounded-full bg-red-500"></span> Break</span>
                        <span class="text-gray-300">|</span>
                        <span v-for="(label, key) in STATUS_LABEL" :key="key" class="flex items-center gap-0.5">
                            {{ label }} {{ key }}
                        </span>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-3 lg:p-4">
                    <FullCalendar :options="calendarOptions">
                        <template v-slot:eventContent="{ event, timeText }">
                            <div
                                class="fc-event-inner"
                                :style="{
                                    opacity:        STATUS_STYLES[event.extendedProps.status]?.opacity,
                                    textDecoration: STATUS_STYLES[event.extendedProps.status]?.textDecoration,
                                }"
                            >
                                <span class="fc-event-status">{{ STATUS_LABEL[event.extendedProps.status] }}</span>
                                <span class="fc-event-time">{{ timeText }}</span>
                                <span class="fc-event-title">{{ event.title }}</span>
                            </div>
                        </template>
                    </FullCalendar>
                </div>

                <!-- Occurrence detail panel -->
                <Transition name="slide-up">
                    <div
                        v-if="selectedOccurrence"
                        class="fixed bottom-6 right-6 w-72 bg-white rounded-xl border border-gray-200 shadow-xl p-4 z-30"
                    >
                        <button
                            @click="selectedOccurrence = null"
                            class="absolute top-3 right-3 text-gray-400 hover:text-gray-600 text-sm"
                        >✕</button>

                        <h3 class="font-semibold text-gray-900 text-sm mb-2 pr-4">{{ selectedOccurrence.title }}</h3>

                        <div class="flex flex-wrap gap-1.5 mb-3">
                            <span
                                class="text-[11px] px-2 py-0.5 rounded-full border font-medium"
                                :class="{
                                    'text-green-700 bg-green-50 border-green-300':   selectedOccurrence.status === 'completed',
                                    'text-indigo-700 bg-indigo-50 border-indigo-300': selectedOccurrence.status === 'pending',
                                    'text-amber-700 bg-amber-50 border-amber-300':   selectedOccurrence.status === 'partial',
                                    'text-red-700 bg-red-50 border-red-300':         selectedOccurrence.status === 'not_completed',
                                    'text-gray-500 bg-gray-50 border-gray-300':      selectedOccurrence.status === 'skipped',
                                }"
                            >
                                {{ STATUS_LABEL[selectedOccurrence.status] }} {{ selectedOccurrence.status }}
                            </span>
                            <span
                                class="text-[11px] px-2 py-0.5 rounded-full font-semibold"
                                :class="selectedOccurrence.habit_nature === 'build'
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'bg-red-50 text-red-700'"
                            >
                                {{ selectedOccurrence.habit_nature_label }}
                            </span>
                        </div>

                        <div class="text-xs text-gray-500 space-y-1">
                            <p><span class="text-gray-700 font-medium">Hora:</span> {{ selectedOccurrence.start.slice(11, 16) }} – {{ selectedOccurrence.end.slice(11, 16) }}</p>
                            <p v-if="selectedOccurrence.completed_at"><span class="text-gray-700 font-medium">Completado:</span> {{ selectedOccurrence.completed_at }}</p>
                            <p><span class="text-gray-700 font-medium">Motivación:</span> {{ selectedOccurrence.desire_type_label }}</p>
                            <p v-if="selectedOccurrence.notes" class="italic border-l-2 border-gray-200 pl-2 mt-1">"{{ selectedOccurrence.notes }}"</p>
                        </div>
                    </div>
                </Transition>
            </div>
        </template>
    </DataProvider>
</template>

<style>

.fc-event-inner {
    display: flex;
    align-items: center;
    gap: 3px;
    padding: 1px 3px;
    font-size: 0.7rem;
    white-space: nowrap;
    overflow: hidden;
    width: 100%;
}
.fc-event-status { flex-shrink: 0; }
.fc-event-time   { flex-shrink: 0; opacity: 0.85; }
.fc-event-title  { overflow: hidden; text-overflow: ellipsis; font-weight: 500; }

.fc .fc-button-primary                                 { background-color: #6366f1; border-color: #6366f1; }
.fc .fc-button-primary:hover                           { background-color: #4f46e5; border-color: #4f46e5; }
.fc .fc-button-primary:not(:disabled).fc-button-active { background-color: #4338ca; border-color: #4338ca; }
.fc-day-today                                          { background-color: #eef2ff !important; }
.fc-timegrid-now-indicator-line                        { border-color: #ef4444 !important; }

/* Mobile toolbar:
   Row 1: [‹ › Hoy]  ........  [1 - 7 mar 2026]
   Row 2:          [Mes] [Sem] [Día]             */
@media (max-width: 640px) {
    .fc .fc-header-toolbar {
        flex-wrap: wrap;
        gap: 6px 0;
        align-items: center;
    }
    /* Row 1 left: prev / next / today */
    .fc .fc-toolbar-chunk:nth-child(1) { order: 1; flex: 0 0 auto; }
    /* Row 1 right: title */
    .fc .fc-toolbar-chunk:nth-child(2) { order: 2; flex: 1; text-align: right; }
    /* Row 2: view selector — full width, centered */
    .fc .fc-toolbar-chunk:nth-child(3) { order: 3; flex: 0 0 100%; display: flex; justify-content: center; }

    .fc .fc-toolbar-title           { font-size: 0.9rem; white-space: nowrap; }
    .fc .fc-button                  { padding: 0.25rem 0.5rem; font-size: 0.72rem; }
    .fc .fc-button-group .fc-button { padding: 0.25rem 0.45rem; }

    .fc .fc-timegrid-axis           { width: 32px !important; min-width: 32px !important; }
    .fc .fc-timegrid-slot-label     { font-size: 0.65rem; }
    .fc .fc-col-header-cell         { font-size: 0.68rem; }
}
</style>