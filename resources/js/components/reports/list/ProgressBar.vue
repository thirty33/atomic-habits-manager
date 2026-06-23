<script setup>
import { computed } from 'vue';
import { adherenceColor } from '@/reports/domain/progress.js';

const props = defineProps({
    percent: { type: Number, default: 0 },
    reported: { type: Number, default: 0 },
    total: { type: Number, default: 0 },
});

const color = computed(() => adherenceColor(props.percent));
</script>

<template>
    <div class="flex items-center gap-2">
        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-line-100">
            <div class="h-full rounded-full" :style="{ width: percent + '%', background: color }"></div>
        </div>
        <span class="font-mono text-[11px] text-ink-500">{{ reported }}/{{ total }}</span>
    </div>
</template>
