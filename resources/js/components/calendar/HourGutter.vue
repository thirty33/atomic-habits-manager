<script setup>
import { computed } from 'vue';

const props = defineProps({
    dayStart: { type: Number, required: true },
    dayEnd: { type: Number, required: true },
    hourH: { type: Number, required: true },
});

const hours = computed(() =>
    Array.from({ length: props.dayEnd - props.dayStart }, (_, i) => props.dayStart + i)
);
const pad = (n) => String(n).padStart(2, '0');
</script>

<template>
    <div class="w-10 shrink-0 select-none">
        <div v-for="h in hours" :key="h" class="relative" :style="{ height: hourH + 'px' }">
            <span class="absolute -top-1.5 right-1 font-mono text-[10px] text-ink-400">{{ pad(h) }}</span>
        </div>
    </div>
</template>
