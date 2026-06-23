<script setup>
import { computed } from 'vue';
import HourGutter from '../HourGutter.vue';
import DayColumn from '../DayColumn.vue';
import { STATUS, statusLabel } from '@/calendar/domain/status.js';
import { blocksOnDay } from '@/calendar/domain/calendar-block.js';

const props = defineProps({
    blocks: { type: Array, required: true },
    anchor: { type: Object, required: true },
    now: { type: Number, required: true },
    today: { type: Object, required: true },
});
const emit = defineEmits(['select']);

const DAY_START = 0;
const DAY_END = 24;
const HOUR_H = 96;

const dayBlocks = computed(() => blocksOnDay(props.blocks, props.anchor.toISO()));
const total = computed(() => dayBlocks.value.length);
const done = computed(() => dayBlocks.value.filter((b) => b.status === 'done').length);
const isToday = computed(() => props.anchor.isSameDay(props.today));
const counts = computed(() =>
    ['done', 'pending', 'partial', 'missed'].map((s) => ({
        s,
        n: dayBlocks.value.filter((b) => b.status === s).length,
    }))
);
</script>

<template>
    <div class="flex flex-col gap-4 lg:flex-row">
        <div class="flex-1 overflow-hidden rounded-xl border border-line-200 bg-card">
            <div class="flex pt-2">
                <HourGutter :day-start="DAY_START" :day-end="DAY_END" :hour-h="HOUR_H" />
                <div class="flex-1" :class="isToday ? 'bg-brand-50/40' : ''">
                    <DayColumn
                        :blocks="blocks"
                        :day-iso="anchor.toISO()"
                        :day-start="DAY_START"
                        :day-end="DAY_END"
                        :hour-h="HOUR_H"
                        :is-today="isToday"
                        :now="now"
                        @select="(id) => emit('select', id)"
                    />
                </div>
            </div>
        </div>
        <aside class="shrink-0 space-y-4 lg:w-[280px]">
            <div class="rounded-xl border border-line-200 bg-card p-4">
                <p class="font-mono text-[11px] uppercase text-ink-400">Resumen del día</p>
                <p class="mt-1 font-display text-[28px] text-ink-900">{{ done }}/{{ total }}</p>
                <div class="mt-2 h-2 overflow-hidden rounded-full bg-line-100">
                    <div class="h-full bg-brand-700" :style="{ width: (total ? (done / total) * 100 : 0) + '%' }"></div>
                </div>
            </div>
            <div class="rounded-xl border border-line-200 bg-card p-4">
                <p class="mb-2 font-mono text-[11px] uppercase text-ink-400">Por estado</p>
                <div v-for="c in counts" :key="c.s" class="flex items-center justify-between py-1 text-[12px]">
                    <span class="inline-flex items-center gap-2 text-ink-700">
                        <span
                            class="h-2.5 w-2.5 rounded-sm"
                            :style="{
                                background: STATUS[c.s].bg,
                                boxShadow: STATUS[c.s].border !== 'transparent' ? 'inset 0 0 0 1px ' + STATUS[c.s].border : 'none',
                            }"
                        ></span>
                        {{ statusLabel(c.s) }}
                    </span>
                    <span class="font-mono text-ink-900">{{ c.n }}</span>
                </div>
            </div>
        </aside>
    </div>
</template>
