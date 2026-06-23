// Reports application controller — reactive editor state + debounced autosave.
// Mirrors the calendar's hexagonal split: the Vue page stays thin and delegates
// here; all I/O goes through the injected gateway port. Preserves the original
// autosave behavior (1500ms debounce, separate entry/report queues, forceSave,
// beforeunload guard, occurrence↔entry merge).

import { reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { debounce } from '@/utils/debounce.js';
import { createSaveQueue } from '@/utils/saveQueue.js';
import { mergeEntries, markServerEntry, createEmptyEntry } from '../domain/report-entry.js';
import { computeProgress, statusCounts } from '../domain/progress.js';

const AUTOSAVE_DELAY = 1500;

export function useReportEditor(gateway) {
    const state = reactive({
        report: null,
        entries: [],
        habits: [],
        moods: [],
        entryStatuses: [],
        loading: true,
        saving: false,
        savingReport: false,
        errors: {},
        lastSavedAt: null,
        pendingEntryChanges: false,
        pendingReportChanges: false,
    });

    const progress = computed(() => computeProgress(state.entries));
    const counts = computed(() => statusCounts(state.entries));

    const saveStatus = computed(() => {
        if (state.saving || state.savingReport) return { label: 'Guardando…', tone: 'saving' };
        if (state.pendingEntryChanges || state.pendingReportChanges) return { label: 'Cambios sin guardar', tone: 'pending' };
        if (state.lastSavedAt) return { label: 'Guardado', tone: 'saved' };
        return null;
    });

    const entriesQueue = createSaveQueue();
    const reportQueue = createSaveQueue();

    async function _saveEntries() {
        state.saving = true;
        state.errors = {};
        try {
            const payload = state.entries.map((e) => ({
                daily_report_entry_id: e.daily_report_entry_id,
                habit_occurrence_id: e.habit_occurrence_id,
                habit_id: e.habit_id,
                custom_activity: e.custom_activity,
                start_time: e.start_time,
                end_time: e.end_time,
                status: e.status,
                notes: e.notes,
            }));
            const { entries } = await gateway.saveEntries(payload);
            if (entries) {
                state.entries = entries.map(markServerEntry);
            }
            state.pendingEntryChanges = false;
            state.lastSavedAt = Date.now();
        } catch (e) {
            if (e?.status === 422) {
                state.errors = e.errors ?? {};
            }
        } finally {
            state.saving = false;
        }
    }

    async function _saveReport() {
        state.savingReport = true;
        try {
            await gateway.updateReport({ notes: state.report.notes, mood: state.report.mood });
            state.pendingReportChanges = false;
            state.lastSavedAt = Date.now();
        } catch (e) {
            if (e?.status === 422) {
                state.errors = { ...state.errors, ...(e.errors ?? {}) };
            }
        } finally {
            state.savingReport = false;
        }
    }

    const debouncedSaveEntries = debounce(() => entriesQueue.enqueue(() => _saveEntries()), AUTOSAVE_DELAY);
    const debouncedSaveReport = debounce(() => reportQueue.enqueue(() => _saveReport()), AUTOSAVE_DELAY);

    async function load() {
        state.loading = true;
        try {
            const data = await gateway.loadEditData();
            state.report = data.report;
            state.habits = data.habits ?? [];
            state.moods = data.moods ?? [];
            state.entryStatuses = data.entryStatuses ?? [];
            state.entries = mergeEntries(data.entries, data.occurrences);
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

    function errorFor(key) {
        return state.errors[key]?.[0] ?? null;
    }

    function handleBeforeUnload(e) {
        if (state.pendingEntryChanges || state.pendingReportChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    }

    onMounted(() => {
        window.addEventListener('beforeunload', handleBeforeUnload);
        load();
    });

    onBeforeUnmount(() => {
        debouncedSaveEntries.cancel();
        debouncedSaveReport.cancel();
        window.removeEventListener('beforeunload', handleBeforeUnload);
    });

    return {
        state, progress, counts, saveStatus,
        load, updateEntry, addEntry, removeEntry, updateReportField, forceSave, errorFor,
    };
}
