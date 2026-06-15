<script>
export default {
    name: 'DashboardChip',
}
</script>

<script setup>
defineProps({
    tone: {
        type: String,
        default: 'neutral',
    },
    dot: {
        type: Boolean,
        default: false,
    },
});

const TONES = {
    brand:   'bg-brand-50 text-brand-800',
    success: 'bg-success-2/10 text-success-2',
    warning: 'bg-warning/10 text-warning',
    neutral: 'bg-line-100 text-ink-700',
    danger:  'bg-danger-2/10 text-danger-2',
};
</script>

<template>
    <span
        class="inline-flex items-center gap-[5px] px-2.5 py-[3px] rounded-full text-[11.5px] font-medium"
        :class="TONES[tone] ?? TONES.neutral"
    >
        <span v-if="dot" class="w-[5px] h-[5px] rounded-full bg-current"></span>
        <slot />
    </span>
</template>
