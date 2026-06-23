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

// 1px gap, small floor (13px) only for very short blocks so blocks keep their
// own time slot and consecutive habits never visually overlap.
const height = computed(() => Math.max((props.segment.end - props.segment.start) * props.hourH - 1, 13));

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

const showText = computed(() => height.value > 11);
const showTime = computed(() => height.value > 11);
const showSub = computed(() => height.value > 28 && props.segment.sub);
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
        class="absolute z-10 cursor-pointer overflow-hidden rounded-md px-1.5 py-0.5 transition hover:brightness-95"
        :style="style"
        @click="emit('select', segment.id)"
    >
        <div v-if="showText" class="flex items-baseline gap-1 leading-none">
            <span class="min-w-0 flex-1 truncate text-[11px] font-medium">{{ segment.name }}<span class="opacity-70">{{ suffix }}</span></span>
            <span v-if="showTime" class="shrink-0 font-mono text-[9.5px] opacity-80">{{ timeLabel }}</span>
        </div>
        <p v-if="showSub" class="mt-0.5 truncate text-[10px] leading-none opacity-70">{{ segment.sub }}</p>
    </div>
</template>
