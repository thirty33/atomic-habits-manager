<script>
export default {
    name: 'CalendarPage',
}
</script>

<script setup>
import FullCalendar from '@fullcalendar/vue3';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import DataProvider from '@/providers/DataProvider.js';
import useDataProvider from '@/composables/useDataProvider.js';
import useCalendar from '@/composables/useCalendar.js';

const props = defineProps({
    jsonUrl: {
        type: String,
        required: true,
    },
    occurrencesUrl: {
        type: String,
        required: true,
    },
});

const { dataProviderKey } = useDataProvider();

const {
    STATUS_LABEL,
    STATUS_STYLES,
    selectedOccurrence,
    clearSelection,
    calendarOptions,
} = useCalendar(props.occurrencesUrl);

calendarOptions.plugins = [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin];
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
                            @click="clearSelection"
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