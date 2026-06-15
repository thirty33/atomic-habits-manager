<script>
export default {
    name: 'DashboardTodayTimeline',
}
</script>

<script setup>
import DashboardChip from './DashboardChip.vue';

defineProps({
    eyebrow: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    summary: {
        type: Array,
        default: () => [],
    },
    rows: {
        type: Array,
        default: () => [],
    },
});

const STATUS = {
    completed:     { tone: 'success', label: 'Hecho',       dot: 'bg-success-2' },
    partial:       { tone: 'warning', label: 'Parcial',     dot: 'bg-warning' },
    in_progress:   { tone: 'brand',   label: 'En curso',    dot: 'bg-brand-700 ring-4 ring-brand-100' },
    not_completed: { tone: 'danger',  label: 'No cumplido', dot: 'bg-danger-2' },
    skipped:       { tone: 'neutral', label: 'Omitido',     dot: 'bg-line-300' },
    pending:       { tone: 'neutral', label: 'Pendiente',   dot: 'bg-line-300' },
};

const statusOf = (status) => STATUS[status] ?? STATUS.pending;
</script>

<template>
    <div class="rounded-xl border border-line-200 bg-card">
        <div class="flex items-center justify-between p-5 pb-3">
            <div>
                <div class="font-mono text-[11px] tracking-[0.12em] uppercase text-ink-400">{{ eyebrow }}</div>
                <div class="font-display text-[22px] mt-1 text-ink-900">{{ title }}</div>
            </div>
            <div class="hidden lg:flex items-center gap-2">
                <DashboardChip v-for="(item, index) in summary" :key="index" :tone="item.tone" dot>
                    {{ item.label }}
                </DashboardChip>
            </div>
        </div>

        <ul class="px-5 pb-5">
            <li
                v-for="(row, index) in rows"
                :key="index"
                class="flex items-center gap-4 py-3 border-t border-line-100"
            >
                <span class="font-mono text-[12.5px] text-ink-500 w-14">{{ row.time }}</span>
                <span class="w-2 h-2 rounded-full shrink-0" :class="statusOf(row.status).dot"></span>
                <div class="flex-1 min-w-0">
                    <div class="text-[14px] text-ink-900 truncate">{{ row.title }}</div>
                    <div class="text-[12px] text-ink-500 truncate">{{ row.detail }}</div>
                </div>
                <DashboardChip :tone="statusOf(row.status).tone">{{ statusOf(row.status).label }}</DashboardChip>
            </li>
        </ul>
    </div>
</template>
