<script setup>
import { computed } from 'vue';
import DateBlock from './DateBlock.vue';
import ReportChip from './ReportChip.vue';
import { adherenceColor } from '@/reports/domain/progress.js';

const props = defineProps({
    editor: { type: Object, required: true },
    backUrl: { type: String, required: true },
});

const report = computed(() => props.editor.state.report);
const progress = computed(() => props.editor.progress.value);
const saveStatus = computed(() => props.editor.saveStatus.value);

const dateLabel = computed(() => {
    const r = report.value;
    if (!r) return '';
    const [y, m, d] = r.report_date.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    const weekday = new Intl.DateTimeFormat('es-ES', { weekday: 'long' }).format(dt);
    const month = new Intl.DateTimeFormat('es-ES', { month: 'long' }).format(dt);
    return `${weekday.charAt(0).toUpperCase()}${weekday.slice(1)} ${d} de ${month}`;
});

const saveTone = { saving: 'text-warning', pending: 'text-ink-400', saved: 'text-success-2' };

async function goBack() {
    await props.editor.forceSave();
    window.location.href = props.backUrl;
}
</script>

<template>
    <div class="sticky top-0 z-20 -mx-4 mb-6 border-b border-line-200 bg-page-cream px-4 pb-4 pt-3 lg:-mx-6 lg:px-6">
        <div class="mb-3 flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
                <DateBlock v-if="report" :date="report.report_date" size="lg" />
                <div>
                    <p class="font-mono text-[11px] uppercase text-ink-400">Reporte diario</p>
                    <h1 class="font-display text-[26px] leading-tight text-ink-900">{{ dateLabel }}</h1>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <ReportChip v-if="progress.isComplete" variant="success" dot>Completo</ReportChip>
                        <ReportChip variant="brand">{{ progress.reported }} de {{ progress.total }} bloques</ReportChip>
                    </div>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span v-if="saveStatus" class="font-mono text-[11px]" :class="saveTone[saveStatus.tone]">
                    {{ saveStatus.label }}<span v-if="saveStatus.tone === 'saved'"> ✓</span>
                </span>
                <button type="button" class="rounded-lg border border-line-200 px-3 py-1.5 text-[12px] text-ink-700 hover:bg-card" @click="goBack">
                    Listado
                </button>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="w-16 font-mono text-[10px] uppercase text-ink-400">Adherencia</span>
            <div class="h-2 flex-1 overflow-hidden rounded-full bg-line-100">
                <div class="h-full rounded-full transition-all" :style="{ width: progress.percent + '%', background: adherenceColor(progress.percent) }"></div>
            </div>
            <span class="w-10 text-right font-mono text-[11px] text-ink-700">{{ progress.percent }}%</span>
        </div>
    </div>
</template>
