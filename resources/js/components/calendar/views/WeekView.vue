<script setup>
import { computed, ref } from 'vue';
import HourGutter from '../HourGutter.vue';
import DayColumn from '../DayColumn.vue';
import { weekDays } from '@/calendar/domain/navigation.js';
import { weekdayShort } from '@/calendar/domain/calendar-date.js';

const props = defineProps({
    blocks: { type: Array, required: true },
    anchor: { type: Object, required: true },
    now: { type: Number, required: true },
    today: { type: Object, required: true },
});
const emit = defineEmits(['select']);

const DAY_START = 0;
const DAY_END = 24;
// Taller rows so short/consecutive habits (e.g. 08:10–08:20, 08:20–08:30) keep
// their own readable slot and never visually overlap.
const HOUR_H = 96;

const days = computed(() => weekDays(props.anchor));

// Mobile single-day selection (defaults to today when the week contains it).
const selectedIso = ref(null);
const defaultIso = computed(() => (days.value.find((d) => d.isSameDay(props.today)) ?? days.value[0]).toISO());
const activeIso = computed(() =>
    days.value.some((d) => d.toISO() === selectedIso.value) ? selectedIso.value : defaultIso.value
);
const activeIsToday = computed(() => activeIso.value === props.today.toISO());
</script>

<template>
    <div>
        <!-- Desktop: full 7-column week grid -->
        <div class="hidden overflow-hidden rounded-xl border border-line-200 bg-card lg:block">
            <div class="overflow-x-auto">
                <div class="min-w-[760px]">
                    <div class="flex border-b border-line-200">
                        <div class="w-10 shrink-0"></div>
                        <div
                            v-for="d in days"
                            :key="d.toISO()"
                            class="min-w-[100px] flex-1 py-2 text-center"
                            :class="d.isSameDay(today) ? 'bg-brand-50' : ''"
                        >
                            <p class="font-mono text-[11px] uppercase text-ink-400">{{ weekdayShort(d) }}</p>
                            <p class="text-[14px]" :class="d.isSameDay(today) ? 'font-semibold text-brand-700' : 'text-ink-700'">
                                {{ d.day }}/{{ d.month }}
                            </p>
                        </div>
                    </div>
                    <div class="flex pt-2">
                        <HourGutter :day-start="DAY_START" :day-end="DAY_END" :hour-h="HOUR_H" />
                        <div
                            v-for="d in days"
                            :key="d.toISO()"
                            class="min-w-[100px] flex-1 border-l border-line-100"
                            :class="d.isSameDay(today) ? 'bg-brand-50/40' : ''"
                        >
                            <DayColumn
                                :blocks="blocks"
                                :day-iso="d.toISO()"
                                :day-start="DAY_START"
                                :day-end="DAY_END"
                                :hour-h="HOUR_H"
                                :is-today="d.isSameDay(today)"
                                :now="now"
                                @select="(id) => emit('select', id)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile: day strip + single day -->
        <div class="lg:hidden">
            <div class="mb-3 flex gap-1.5 overflow-x-auto pb-1">
                <button
                    v-for="d in days"
                    :key="d.toISO()"
                    type="button"
                    class="flex min-w-[46px] shrink-0 flex-col items-center rounded-lg border px-2 py-1.5 transition"
                    :class="d.toISO() === activeIso
                        ? 'border-brand-700 bg-brand-700 text-[#f4ead6]'
                        : (d.isSameDay(today) ? 'border-brand-200 bg-brand-50 text-brand-700' : 'border-line-200 text-ink-500')"
                    @click="selectedIso = d.toISO()"
                >
                    <span class="font-mono text-[10px] uppercase">{{ weekdayShort(d) }}</span>
                    <span class="text-[15px] font-semibold">{{ d.day }}</span>
                </button>
            </div>
            <div class="overflow-hidden rounded-xl border border-line-200 bg-card">
                <div class="flex pt-2">
                    <HourGutter :day-start="DAY_START" :day-end="DAY_END" :hour-h="HOUR_H" />
                    <div class="flex-1" :class="activeIsToday ? 'bg-brand-50/40' : ''">
                        <DayColumn
                            :blocks="blocks"
                            :day-iso="activeIso"
                            :day-start="DAY_START"
                            :day-end="DAY_END"
                            :hour-h="HOUR_H"
                            :is-today="activeIsToday"
                            :now="now"
                            @select="(id) => emit('select', id)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
