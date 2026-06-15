<script>
export default {
    name: 'DashboardRecentReflection',
}
</script>

<script setup>
import DashboardChip from './DashboardChip.vue';

defineProps({
    eyebrow: {
        type: String,
        default: '',
    },
    mood: {
        type: Object,
        default: () => ({}),
    },
    text: {
        type: String,
        default: '',
    },
    datetime: {
        type: String,
        default: '',
    },
    linkLabel: {
        type: String,
        default: '',
    },
    reportLink: {
        type: String,
        default: null,
    },
});
</script>

<template>
    <div class="rounded-xl border border-line-200 bg-card p-5">
        <div class="flex items-center justify-between">
            <div class="font-mono text-[11px] tracking-[0.12em] uppercase text-ink-400">{{ eyebrow }}</div>
            <DashboardChip :tone="mood.tone" dot>{{ mood.label }}</DashboardChip>
        </div>

        <p class="mt-3 font-display text-[18px] text-ink-900 leading-snug">{{ text }}</p>

        <div class="mt-3 flex items-center justify-between text-[11.5px] text-ink-500">
            <span class="font-mono">{{ datetime }}</span>
            <a v-if="reportLink" :href="reportLink" class="text-brand-700 uline">{{ linkLabel }}</a>
        </div>
    </div>
</template>
