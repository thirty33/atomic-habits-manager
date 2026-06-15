<script>
export default {
    name: 'DashboardKpiCard',
}
</script>

<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: String,
        required: true,
    },
    delta: {
        type: String,
        default: null,
    },
    sublabel: {
        type: String,
        default: '',
    },
});

const isUp = (delta) => typeof delta === 'string' && delta.startsWith('+');
</script>

<template>
    <div class="relative overflow-hidden bg-card border border-line-200 rounded-xl p-[22px]">
        <div class="flex items-center justify-between gap-2">
            <span class="min-w-0 truncate font-mono text-[10.5px] tracking-[0.12em] uppercase text-ink-400">{{ label }}</span>
            <span
                v-if="delta"
                class="shrink-0 inline-flex items-center gap-0.5 text-[12px] leading-none px-2 py-1 rounded-full whitespace-nowrap"
                :class="isUp(delta) ? 'bg-brand-50 text-brand-800' : 'bg-danger-2/10 text-danger-2'"
            >
                <svg width="9" height="9" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <template v-if="isUp(delta)"><path d="M6 10V2M6 2l-3.5 3.5M6 2l3.5 3.5" /></template>
                    <template v-else><path d="M6 2v8M6 10l-3.5-3.5M6 10l3.5 3.5" /></template>
                </svg>
                {{ delta.replace(/[+-]/, '') }}
            </span>
        </div>

        <div class="font-display text-[56px] leading-none text-ink-900 mt-3.5 tracking-[-0.02em]">{{ value }}</div>

        <div v-if="sublabel" class="mt-2.5 text-[12px] text-ink-500">{{ sublabel }}</div>
    </div>
</template>
