<script setup>
import { computed } from 'vue';
import { fmtHour } from '@/calendar/domain/calendar-date.js';

const props = defineProps({
    now: { type: Number, required: true },
    dayStart: { type: Number, required: true },
    hourH: { type: Number, required: true },
    showLabel: { type: Boolean, default: true },
});

const top = computed(() => (props.now - props.dayStart) * props.hourH);
const visible = computed(() => props.now >= props.dayStart);
</script>

<template>
    <div
        v-if="visible"
        class="pointer-events-none absolute left-0 right-0 z-20 flex items-center gap-1"
        :style="{ top: top + 'px' }"
    >
        <span class="h-2 w-2 rounded-full" style="background: rgb(var(--color-danger-2))"></span>
        <span class="h-px flex-1" style="background: rgb(var(--color-danger-2))"></span>
        <span
            v-if="showLabel"
            class="rounded px-1.5 py-0.5 font-mono text-[10px]"
            style="color: rgb(var(--color-danger-2)); box-shadow: inset 0 0 0 1px rgb(var(--color-danger-2));"
        >
            {{ fmtHour(now) }}
        </span>
    </div>
</template>
