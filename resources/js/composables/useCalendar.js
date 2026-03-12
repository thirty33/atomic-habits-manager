import { ref } from 'vue';

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
            status:              occurrence.status ?? 'pending',
            completed_at:        occurrence.completed_at ?? null,
            notes:               occurrence.notes ?? null,
            update_action:       occurrence.update_action ?? null,
            habit_id:            habit.habit_id,
            habit_nature:        habit.habit_nature,
            habit_nature_label:  habit.habit_nature_label,
            desire_type_label:   habit.desire_type_label,
        },
    };
}

function fetchOccurrences(url) {
    return (fetchInfo, successCallback, failureCallback) => {
        axios.get(url, {
            params: {
                start: fetchInfo.startStr,
                end: fetchInfo.endStr,
            },
        })
        .then(response => {
            successCallback(response.data.data.map(occurrenceToEvent));
        })
        .catch(error => {
            failureCallback(error);
        });
    };
}

export default function useCalendar(occurrencesUrl) {
    const selectedOccurrence = ref(null);

    const selectOccurrence = (event) => {
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
    };

    const clearSelection = () => {
        selectedOccurrence.value = null;
    };

    const calendarOptions = {
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
        slotEventOverlap: false,
        events: fetchOccurrences(occurrencesUrl),
        eventClick({ event }) {
            selectOccurrence(event);
        },
    };

    return {
        STATUS_LABEL,
        STATUS_STYLES,
        NATURE_DEFAULT_COLOR,
        selectedOccurrence,
        selectOccurrence,
        clearSelection,
        calendarOptions,
    };
}