<script>
export default {
    name: 'DailyReportPage',
}
</script>

<script setup>
import { onMounted, ref } from 'vue';
import useDailyReport from '@/composables/useDailyReport.js';
import ReportEntryCard from '@/components/daily-reports/ReportEntryCard.vue';
import { AppSpinner } from '@/components/ui';

const props = defineProps({
    jsonUrl: { type: String, required: true },
    saveEntriesUrl: { type: String, required: true },
    updateReportUrl: { type: String, required: true },
    backUrl: { type: String, required: true },
});

const {
    report, entries, habits, moods,
    loading, saving, savingReport, errors,
    progressSummary, isComplete, progressPercent,
    loadData, addEntry, removeEntry, updateEntry,
    saveEntries, updateReport,
} = useDailyReport(props.jsonUrl, props.saveEntriesUrl, props.updateReportUrl);

const reportNotes = ref('');
const reportMood = ref('');

onMounted(async () => {
    await loadData();
    reportNotes.value = report.value?.notes ?? '';
    reportMood.value = report.value?.mood ?? '';
});

async function handleSaveEntries() {
    try {
        await saveEntries();
    } catch { /* errors handled in composable */ }
}

async function handleUpdateReport() {
    try {
        await updateReport({ notes: reportNotes.value, mood: reportMood.value || null });
    } catch { /* errors handled in composable */ }
}
</script>

<template>
    <div class="max-w-3xl mx-auto">
        <!-- Loading -->
        <AppSpinner v-if="loading" />

        <template v-if="!loading && report">
            <!-- Header -->
            <div class="sticky top-0 z-10 bg-gray-50 pb-4 mb-6 -mx-4 px-4 pt-2 border-b border-gray-200">
                <div class="flex items-center justify-between mb-2">
                    <a :href="backUrl" class="text-sm text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                        Volver al listado
                    </a>
                    <span
                        v-if="isComplete"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                    >
                        Completo
                    </span>
                </div>

                <h1 class="text-xl font-bold text-gray-900">
                    Reporte del {{ report.report_date }}
                </h1>

                <!-- Progress bar -->
                <div class="mt-3">
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>{{ progressSummary.reported }}/{{ progressSummary.total }} reportados</span>
                        <span>{{ progressPercent }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div
                            class="h-2 rounded-full transition-all duration-300"
                            :class="isComplete ? 'bg-green-500' : 'bg-indigo-500'"
                            :style="{ width: progressPercent + '%' }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Entry cards -->
            <div class="space-y-4 mb-6">
                <ReportEntryCard
                    v-for="(entry, index) in entries"
                    :key="entry.daily_report_entry_id ?? `new-${index}`"
                    :entry="entry"
                    :habits="habits"
                    :index="index"
                    :errors="errors"
                    @update="updateEntry"
                    @remove="removeEntry"
                />
            </div>

            <!-- Add activity button -->
            <button
                @click="addEntry"
                class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-indigo-400 hover:text-indigo-600 transition-colors"
            >
                + Añadir actividad
            </button>

            <!-- Save entries button -->
            <div class="mt-4">
                <button
                    @click="handleSaveEntries"
                    :disabled="saving"
                    class="w-full py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                >
                    {{ saving ? 'Guardando...' : 'Guardar entradas' }}
                </button>
            </div>

            <!-- Reflection section -->
            <div class="mt-8 bg-white rounded-xl shadow-sm p-5 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Reflexión del día</h2>

                <div>
                    <label class="block text-sm text-gray-600 mb-2">¿Cómo te sentiste hoy?</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="mood in moods"
                            :key="mood.value"
                            @click="reportMood = mood.value"
                            class="px-3 py-1.5 rounded-full text-sm border transition-all"
                            :class="reportMood === mood.value
                                ? 'bg-indigo-100 text-indigo-800 border-indigo-300 ring-2 ring-indigo-500'
                                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                        >
                            {{ mood.emoji }} {{ mood.label }}
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-1">Notas</label>
                    <textarea
                        v-model="reportNotes"
                        placeholder="¿Algo que quieras anotar sobre este día?"
                        rows="3"
                        class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                    ></textarea>
                </div>

                <button
                    @click="handleUpdateReport"
                    :disabled="savingReport"
                    class="px-4 py-2 bg-gray-800 text-white rounded-lg text-sm font-medium hover:bg-gray-900 disabled:opacity-50 transition-colors"
                >
                    {{ savingReport ? 'Guardando...' : 'Guardar reflexión' }}
                </button>
            </div>
        </template>
    </div>
</template>