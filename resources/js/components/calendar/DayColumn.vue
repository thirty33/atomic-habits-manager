<script setup>
import { computed } from 'vue';
import GridBlock from './GridBlock.vue';
import NowLine from './NowLine.vue';
import { layoutDay, segmentIsNow } from '@/calendar/domain/calendar-block.js';

const props = defineProps({
    blocks: { type: Array, required: true },
    dayIso: { type: String, required: true },
    dayStart: { type: Number, required: true },
    dayEnd: { type: Number, required: true },
    hourH: { type: Number, required: true },
    isToday: { type: Boolean, default: false },
    now: { type: Number, default: 0 },
});
const emit = defineEmits(['select']);

const gridH = computed(() => (props.dayEnd - props.dayStart) * props.hourH);

const segments = computed(() =>
    layoutDay(props.blocks, props.dayIso).filter(
        (seg) => seg.end > props.dayStart && seg.start < props.dayEnd
    )
);

const hourLines = computed(
    () =>
        `repeating-linear-gradient(to bottom, transparent 0, transparent ${props.hourH - 1}px, rgb(var(--color-line-100)) ${props.hourH - 1}px, rgb(var(--color-line-100)) ${props.hourH}px)`
);
</script>

<template>
    <div class="relative" :style="{ height: gridH + 'px', backgroundImage: hourLines }">
        <GridBlock
            v-for="seg in segments"
            :key="seg.id + '-' + seg.part"
            :segment="seg"
            :hour-h="hourH"
            :day-start="dayStart"
            :is-now="isToday && segmentIsNow(seg, now, dayIso)"
            @select="(id) => emit('select', id)"
        />
        <NowLine v-if="isToday" :now="now" :day-start="dayStart" :hour-h="hourH" />
    </div>
</template>
