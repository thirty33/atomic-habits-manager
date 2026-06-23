<script>
export default { name: 'DailyReportsBoard' };
</script>

<script setup>
import { computed, onMounted } from 'vue';
import { HttpReportsListGateway } from '@/reports/list/http-reports-list-gateway.js';
import { useReportsList } from '@/reports/list/use-reports-list.js';
import { moodVariant } from '@/reports/domain/status.js';
import DateBlock from '@/components/reports/DateBlock.vue';
import ReportChip from '@/components/reports/ReportChip.vue';
import ProgressBar from '@/components/reports/list/ProgressBar.vue';
import ReportsFilterBar from '@/components/reports/list/ReportsFilterBar.vue';
import Pagination from '@/components/reports/list/Pagination.vue';
import { AppSpinner } from '@/components/ui';

const props = defineProps({
    boardJsonUrl: { type: String, required: true },
    todayUrl: { type: String, required: true },
});

const { state, load, applyFilters, clearFilters, goPage, remove } = useReportsList(
    new HttpReportsListGateway(props.boardJsonUrl)
);

onMounted(load);

const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1);
const weekday = (dateStr) => {
    const [y, m, d] = dateStr.split('-').map(Number);
    return cap(new Intl.DateTimeFormat('es-ES', { weekday: 'long' }).format(new Date(y, m - 1, d)));
};
const total = computed(() => state.meta.total ?? 0);
</script>

<template>
    <div class="px-4 py-4 lg:px-6 lg:py-6">
        <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="font-mono text-[11px] uppercase text-ink-400">Histórico · {{ total }} reportes</p>
                <h1 class="font-display text-[28px] leading-tight text-ink-900">Reporte diario</h1>
                <p class="text-[13px] text-ink-500">Tu registro día a día de hábitos cumplidos.</p>
            </div>
            <a :href="todayUrl" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-700 px-3.5 py-2 text-[13px] text-[#f4ead6] hover:bg-brand-800">
                <span class="text-base leading-none">+</span> Reporte de hoy
            </a>
        </div>

        <ReportsFilterBar :filters="state.filters" :moods="state.moods" @apply="applyFilters" @clear="clearFilters" />

        <div v-if="state.loading" class="flex justify-center py-16"><AppSpinner /></div>

        <template v-else>
            <!-- Desktop table -->
            <div class="hidden overflow-hidden rounded-xl border border-line-200 bg-card lg:block">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-line-200 text-[11px] uppercase text-ink-400">
                            <th class="px-4 py-3 font-mono font-normal">Fecha</th>
                            <th class="px-4 py-3 font-mono font-normal">Estado de ánimo</th>
                            <th class="px-4 py-3 font-mono font-normal">Progreso</th>
                            <th class="px-4 py-3 font-mono font-normal">Reflexión</th>
                            <th class="px-4 py-3 font-mono font-normal">Estado</th>
                            <th class="px-4 py-3 text-right font-mono font-normal">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="state.rows.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-[13px] text-ink-400">No hay reportes para los filtros seleccionados.</td>
                        </tr>
                        <tr v-for="row in state.rows" :key="row.daily_report_id" class="border-b border-line-100 last:border-0">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <DateBlock :date="row.report_date" size="sm" />
                                    <span class="text-[13px] text-ink-700">{{ weekday(row.report_date) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <ReportChip v-if="row.mood" :variant="moodVariant(row.mood)">{{ row.mood_label }}</ReportChip>
                                <span v-else class="font-mono text-[12px] text-ink-400">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <ProgressBar :percent="row.progress_percent" :reported="row.entries_reported" :total="row.entries_count" />
                            </td>
                            <td class="max-w-[260px] px-4 py-3">
                                <span v-if="row.notes" class="line-clamp-1 text-[13px] italic text-ink-700">«{{ row.notes }}»</span>
                                <span v-else class="font-mono text-[12px] text-ink-400">— sin notas</span>
                            </td>
                            <td class="px-4 py-3">
                                <ReportChip :variant="row.is_complete ? 'success' : 'warning'" dot>{{ row.is_complete ? 'Completo' : 'Borrador' }}</ReportChip>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1">
                                    <a :href="row.edit_url" class="rounded-lg p-1.5 text-ink-400 hover:bg-page-cream hover:text-brand-700" title="Editar">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                                    </a>
                                    <button type="button" class="rounded-lg p-1.5 text-ink-400 hover:bg-danger-2/10 hover:text-danger-2" title="Eliminar" @click="remove(row)">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile cards -->
            <div class="space-y-3 lg:hidden">
                <p v-if="state.rows.length === 0" class="rounded-xl border border-dashed border-line-200 bg-page-cream py-10 text-center text-[13px] text-ink-400">
                    No hay reportes para los filtros seleccionados.
                </p>
                <article v-for="row in state.rows" :key="row.daily_report_id" class="rounded-xl border border-line-200 bg-card p-4">
                    <div class="flex items-start gap-3">
                        <DateBlock :date="row.report_date" size="sm" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[13px] text-ink-700">{{ weekday(row.report_date) }}</span>
                                <ReportChip :variant="row.is_complete ? 'success' : 'warning'" dot small>{{ row.is_complete ? 'Completo' : 'Borrador' }}</ReportChip>
                            </div>
                            <div class="mt-1.5 flex items-center gap-2">
                                <ReportChip v-if="row.mood" :variant="moodVariant(row.mood)" small>{{ row.mood_label }}</ReportChip>
                                <ProgressBar :percent="row.progress_percent" :reported="row.entries_reported" :total="row.entries_count" />
                            </div>
                        </div>
                    </div>
                    <p v-if="row.notes" class="mt-2 line-clamp-2 text-[12.5px] italic text-ink-700">«{{ row.notes }}»</p>
                    <div class="mt-3 flex items-center justify-end gap-2">
                        <a :href="row.edit_url" class="rounded-lg border border-line-200 px-3 py-1.5 text-[12px] text-ink-700 hover:bg-page-cream">Editar</a>
                        <button type="button" class="rounded-lg border border-line-200 px-3 py-1.5 text-[12px] text-danger-2 hover:bg-danger-2/10" @click="remove(row)">Eliminar</button>
                    </div>
                </article>
            </div>

            <Pagination :meta="state.meta" @go="goPage" />
        </template>
    </div>
</template>
