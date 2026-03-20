import { reactive, computed, inject, onBeforeUnmount } from 'vue';
import { debounce } from '@/utils/debounce.js';
import { createSaveQueue } from '@/utils/saveQueue.js';
import { STATUS_CONFIG } from '@/constants/reportEntryStatus.js';

const AUTOSAVE_DELAY = 1500;

function createEmptyEntry() {
    return {
        daily_report_entry_id: null,
        habit_occurrence_id: null,
        habit_id: null,
        custom_activity: null,
        start_time: '',
        end_time: '',
        status: 'pending',
        notes: null,
        is_free_activity: true,
        habit: null,
    };
}

function occurrenceToEntry(occurrence) {
    return {
        daily_report_entry_id: null,
        habit_occurrence_id: occurrence.habit_occurrence_id,
        habit_id: occurrence.habit_id,
        custom_activity: null,
        start_time: occurrence.start_time,
        end_time: occurrence.end_time,
        status: 'pending',
        notes: null,
        is_free_activity: false,
        habit: occurrence.habit ?? null,
    };
}

function sortByStartTime(a, b) {
    return (a.start_time || '').localeCompare(b.start_time || '');
}

export default function useReportStore(jsonUrl, saveEntriesUrl, updateReportUrl) {
    const addSuccessToast = inject('addSuccessToast', () => {});

    const state = reactive({
        report: null,
        entries: [],
        habits: [],
        moods: [],
        loading: true,
        saving: false,
        savingReport: false,
        errors: {},
        lastSavedAt: null,
        pendingEntryChanges: false,
        pendingReportChanges: false,
    });

    // --- Computed ---

    const progressSummary = computed(() => {
        const total = state.entries.length;
        const reported = state.entries.filter(e => e.status !== 'pending').length;
        const completed = state.entries.filter(e => e.status === 'completed').length;
        return { total, reported, completed };
    });

    const isComplete = computed(() => {
        const { total, reported } = progressSummary.value;
        return total > 0 && reported === total;
    });

    const progressPercent = computed(() => {
        const { total, reported } = progressSummary.value;
        return total > 0 ? Math.round((reported / total) * 100) : 0;
    });

    // --- Queues ---

    const entriesQueue = createSaveQueue();
    const reportQueue = createSaveQueue();

    // --- API calls ---

    async function _saveEntries() {
        state.saving = true;
        state.errors = {};

        try {
            const payload = state.entries.map(e => ({
                daily_report_entry_id: e.daily_report_entry_id,
                habit_occurrence_id: e.habit_occurrence_id,
                habit_id: e.habit_id,
                custom_activity: e.custom_activity,
                start_time: e.start_time,
                end_time: e.end_time,
                status: e.status,
                notes: e.notes,
            }));

            const { data } = await axios.put(saveEntriesUrl, { entries: payload });

            if (data?.extra?.entries) {
                const serverEntries = data.extra.entries.map(e => ({
                    ...e,
                    is_free_activity: !e.habit_id && !e.habit_occurrence_id,
                }));
                state.entries = serverEntries.sort(sortByStartTime);
            }

            state.pendingEntryChanges = false;
            state.lastSavedAt = Date.now();
        } catch (e) {
            if (e.response?.status === 422) {
                state.errors = e.response.data.errors;
            }
        } finally {
            state.saving = false;
        }
    }

    async function _saveReport() {
        state.savingReport = true;

        try {
            await axios.put(updateReportUrl, {
                notes: state.report.notes,
                mood: state.report.mood,
            });

            state.pendingReportChanges = false;
            state.lastSavedAt = Date.now();
        } catch (e) {
            if (e.response?.status === 422) {
                state.errors = { ...state.errors, ...e.response.data.errors };
            }
        } finally {
            state.savingReport = false;
        }
    }

    // --- Debounced auto-save ---

    const debouncedSaveEntries = debounce(() => {
        entriesQueue.enqueue(() => _saveEntries());
    }, AUTOSAVE_DELAY);

    const debouncedSaveReport = debounce(() => {
        reportQueue.enqueue(() => _saveReport());
    }, AUTOSAVE_DELAY);

    // --- Actions ---

    async function loadData() {
        state.loading = true;

        try {
            const { data } = await axios.get(jsonUrl);

            state.report = data.report;
            state.habits = data.habits;
            state.moods = data.moods;

            const existingEntries = (data.entries ?? []).map(e => ({
                ...e,
                is_free_activity: !e.habit_id && !e.habit_occurrence_id,
            }));

            const usedOccurrenceIds = new Set(
                existingEntries.map(e => e.habit_occurrence_id).filter(Boolean)
            );

            const newFromOccurrences = (data.occurrences ?? [])
                .filter(occ => !usedOccurrenceIds.has(occ.habit_occurrence_id))
                .map(occurrenceToEntry);

            state.entries = [...existingEntries, ...newFromOccurrences].sort(sortByStartTime);
        } catch (e) {
            console.error('Error loading report data', e);
        } finally {
            state.loading = false;
        }
    }

    function updateEntry(index, fields) {
        state.entries[index] = { ...state.entries[index], ...fields };
        state.pendingEntryChanges = true;
        debouncedSaveEntries();
    }

    function addEntry() {
        state.entries.push(createEmptyEntry());
        state.pendingEntryChanges = true;
        debouncedSaveEntries();
    }

    function removeEntry(index) {
        state.entries.splice(index, 1);
        state.pendingEntryChanges = true;
        debouncedSaveEntries();
    }

    function updateReportField(field, value) {
        if (state.report) {
            state.report[field] = value;
            state.pendingReportChanges = true;
            debouncedSaveReport();
        }
    }

    async function forceSave() {
        debouncedSaveEntries.cancel();
        debouncedSaveReport.cancel();

        const promises = [];

        if (state.pendingEntryChanges) {
            promises.push(entriesQueue.enqueue(() => _saveEntries()));
        }
        if (state.pendingReportChanges) {
            promises.push(reportQueue.enqueue(() => _saveReport()));
        }

        await Promise.all(promises);
    }

    // --- Cleanup ---

    function handleBeforeUnload(e) {
        if (state.pendingEntryChanges || state.pendingReportChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    }

    function setup() {
        window.addEventListener('beforeunload', handleBeforeUnload);
    }

    function teardown() {
        debouncedSaveEntries.cancel();
        debouncedSaveReport.cancel();
        window.removeEventListener('beforeunload', handleBeforeUnload);
    }

    return {
        state,
        STATUS_CONFIG,
        progressSummary,
        isComplete,
        progressPercent,
        loadData,
        updateEntry,
        addEntry,
        removeEntry,
        updateReportField,
        forceSave,
        setup,
        teardown,
    };
}