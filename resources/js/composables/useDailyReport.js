import { ref, computed } from 'vue';
import useAxios from '@/composables/useAxios.js';
import { STATUS_CONFIG } from '@/constants/reportEntryStatus.js';

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

export default function useDailyReport(jsonUrl, saveEntriesUrl, updateReportUrl) {
    const report = ref(null);
    const entries = ref([]);
    const habits = ref([]);
    const entryStatuses = ref([]);
    const moods = ref([]);
    const loading = ref(true);
    const saving = ref(false);
    const savingReport = ref(false);
    const errors = ref({});

    const progressSummary = computed(() => {
        const total = entries.value.length;
        const reported = entries.value.filter(e => e.status !== 'pending').length;
        const completed = entries.value.filter(e => e.status === 'completed').length;

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

    async function loadData() {
        loading.value = true;

        try {
            const { data } = await axios.get(jsonUrl);

            report.value = data.report;
            habits.value = data.habits;
            entryStatuses.value = data.entry_statuses;
            moods.value = data.moods;

            // Merge existing entries with unmatched occurrences
            const existingEntries = (data.entries ?? []).map(e => ({
                ...e,
                is_free_activity: !e.habit_id && !e.habit_occurrence_id,
            }));

            // Find occurrences that don't have an entry yet
            const usedOccurrenceIds = new Set(
                existingEntries.map(e => e.habit_occurrence_id).filter(Boolean)
            );

            const newFromOccurrences = (data.occurrences ?? [])
                .filter(occ => !usedOccurrenceIds.has(occ.habit_occurrence_id))
                .map(occurrenceToEntry);

            entries.value = [...existingEntries, ...newFromOccurrences]
                .sort((a, b) => (a.start_time || '').localeCompare(b.start_time || ''));
        } catch (e) {
            console.error('Error loading report data', e);
        } finally {
            loading.value = false;
        }
    }

    function addEntry() {
        entries.value.push(createEmptyEntry());
    }

    function removeEntry(index) {
        entries.value.splice(index, 1);
    }

    function updateEntry(index, updated) {
        entries.value[index] = { ...entries.value[index], ...updated };
    }

    async function saveEntries() {
        saving.value = true;
        errors.value = {};

        try {
            const { makeRequest } = useAxios();
            const payload = entries.value.map(e => ({
                daily_report_entry_id: e.daily_report_entry_id,
                habit_occurrence_id: e.habit_occurrence_id,
                habit_id: e.habit_id,
                custom_activity: e.custom_activity,
                start_time: e.start_time,
                end_time: e.end_time,
                status: e.status,
                notes: e.notes,
            }));

            const { data } = await makeRequest({
                method: 'put',
                url: saveEntriesUrl,
                data: { entries: payload },
            });

            // Update entries with server response (IDs assigned)
            if (data?.extra?.entries) {
                const serverEntries = data.extra.entries.map(e => ({
                    ...e,
                    is_free_activity: !e.habit_id && !e.habit_occurrence_id,
                }));
                entries.value = serverEntries.sort(
                    (a, b) => (a.start_time || '').localeCompare(b.start_time || '')
                );
            }

            return data;
        } catch (e) {
            if (e.response?.status === 422) {
                errors.value = e.response.data.errors;
            }
            throw e;
        } finally {
            saving.value = false;
        }
    }

    async function updateReport(reportData) {
        savingReport.value = true;

        try {
            const { makeRequest } = useAxios();
            const { data } = await makeRequest({
                method: 'put',
                url: updateReportUrl,
                data: reportData,
            });

            return data;
        } catch (e) {
            if (e.response?.status === 422) {
                errors.value = { ...errors.value, ...e.response.data.errors };
            }
            throw e;
        } finally {
            savingReport.value = false;
        }
    }

    return {
        STATUS_CONFIG,
        report,
        entries,
        habits,
        entryStatuses,
        moods,
        loading,
        saving,
        savingReport,
        errors,
        progressSummary,
        isComplete,
        progressPercent,
        loadData,
        addEntry,
        removeEntry,
        updateEntry,
        saveEntries,
        updateReport,
    };
}