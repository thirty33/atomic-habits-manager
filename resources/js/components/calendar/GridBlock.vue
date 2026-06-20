<script setup>
import { computed } from 'vue';
import { statusStyle, accentColor, NOW_RING } from '@/calendar/domain/status.js';

const props = defineProps({
    segment: { type: Object, required: true },
    hourH: { type: Number, required: true },
    dayStart: { type: Number, required: true },
    isNow: { type: Boolean, default: false },
});
const emit = defineEmits(['select']);

const height = computed(() => Math.max((props.segment.end - props.segment.start) * props.hourH - 3, 16));

const style = computed(() => {
    const seg = props.segment;
    const st = statusStyle(seg.status);
    return {
        top: `${(seg.start - props.dayStart) * props.hourH}px`,
        height: `${height.value}px`,
        left: `calc(${(seg.slot / seg.slots) * 100}% + 3px)`,
        width: `calc(${100 / seg.slots}% - 6px)`,
        background: st.bg,
        color: st.fg,
        borderLeft: `3px solid ${accentColor(seg.accent)}`,
        boxShadow: props.isNow
            ? `0 0 0 2px ${NOW_RING}`
            : st.border !== 'transparent'
                ? `inset 0 0 0 1px ${st.border}`
                : 'none',
    };
});

const showText = computed(() => height.value > 20);
const showTime = computed(() => height.value > 34);
const showSub = computed(() => height.value > 56 && props.segment.sub);
const suffix = computed(() => {
    if (props.segment.part === 'tail') return ' · viene de anoche';
    if (props.segment.part === 'head') return ' · sigue mañana';
    return '';
});
const timeLabel = computed(() =>
    props.segment.overnight
        ? `${props.segment.startTime} → ${props.segment.endTime}`
        : `${props.segment.startTime}–${props.segment.endTime}`
);
</script>

<template>
    <div
        class="absolute z-10 cursor-pointer overflow-hidden rounded-md px-2 py-1 transition hover:brightness-95"
        :style="style"
        @click="emit('select', segment.id)"
    >
        <p v-if="showText" class="truncate text-[11px] font-medium leading-tight">
            {{ segment.name }}<span class="opacity-70">{{ suffix }}</span>
        </p>
        <p v-if="showTime" class="truncate font-mono text-[10px] leading-tight opacity-80">{{ timeLabel }}</p>
        <p v-if="showSub" class="truncate text-[10px] leading-tight opacity-70">{{ segment.sub }}</p>
    </div>
</template>
