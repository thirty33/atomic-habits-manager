<script>
export default {
    name: 'ReportHeader',
}
</script>

<script setup>
import { inject, computed } from 'vue';
import ReportProgressBar from '@/components/daily-reports/ReportProgressBar.vue';

const props = defineProps({
    backUrl: { type: String, required: true },
});

const store = inject('reportStore');

const saveStatusLabel = computed(() => {
    if (store.state.saving || store.state.savingReport) return 'Guardando...';
    if (store.state.pendingEntryChanges || store.state.pendingReportChanges) return 'Cambios sin guardar';
    if (store.state.lastSavedAt) return 'Guardado ✓';
    return null;
});

const saveStatusClass = computed(() => {
    if (store.state.saving || store.state.savingReport) return 'text-amber-600';
    if (store.state.pendingEntryChanges || store.state.pendingReportChanges) return 'text-amber-500';
    return 'text-green-600';
});

async function handleBack() {
    await store.forceSave();
    window.location.href = props.backUrl;
}
</script>

<template>
    <div class="sticky top-0 z-20 bg-page-bg pb-4 mb-6 -mx-4 px-4 pt-2 border-b border-gray-200">
        <div class="flex items-center justify-between mb-2">
            <a
                href="#"
                @click.prevent="handleBack"
                class="inline-flex items-center gap-1 text-white bg-btn-secondary hover:bg-btn-secondary-hover focus:ring-4 focus:outline-none focus:ring-btn-secondary/30 font-medium rounded-lg text-sm px-4 py-2 text-center transition-colors"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                Volver al listado
            </a>

            <div class="flex items-center gap-3">
                <span
                    v-if="saveStatusLabel"
                    class="text-xs transition-colors"
                    :class="saveStatusClass"
                >
                    {{ saveStatusLabel }}
                </span>

                <span
                    v-if="store.isComplete.value"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                >
                    Completo
                </span>
            </div>
        </div>

        <h1 class="text-xl font-bold text-gray-900">
            Reporte del {{ store.state.report?.report_date }}
        </h1>

        <ReportProgressBar />
    </div>
</template>