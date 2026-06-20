<script setup>
import { computed, ref } from 'vue';
import MonthChip from '../MonthChip.vue';
import StatusChip from '../StatusChip.vue';
import EmptyState from '../EmptyState.vue';
import { statusStyle, accentColor } from '@/calendar/domain/status.js';
import { monthGridDays } from '@/calendar/domain/navigation.js';
import { blocksOnDay } from '@/calendar/domain/calendar-block.js';
import { CalendarDate, weekdayLong, monthLong } from '@/calendar/domain/calendar-date.js';

const props = defineProps({
    blocks: { type: Array, required: true },
    anchor: { type: Object, required: true },
    today: { type: Object, required: true },
});
const emit = defineEmits(['selectDay', 'select']);

const HEAD = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
const HEAD_SHORT = ['D', 'L', 'M', 'M', 'J', 'V', 'S'];
const days = computed(() => monthGridDays(props.anchor));
const chipsFor = (d) => blocksOnDay(props.blocks, d.toISO());

// Mobile selected day (defaults to today when in month, else the 1st).
const selectedIso = ref(null);
const defaultIso = computed(() =>
    (props.today.isSameMonth(props.anchor) ? props.today : props.anchor.startOfMonth()).toISO()
);
const activeIso = computed(() =>
    days.value.some((d) => d.toISO() === selectedIso.value) ? selectedIso.value : defaultIso.value
);
const activeDate = computed(() => CalendarDate.parse(activeIso.value));
const activeRows = computed(() => blocksOnDay(props.blocks, activeIso.value));
</script>

<template>
    <div>
        <!-- Desktop: chips grid -->
        <div class="hidden overflow-hidden rounded-xl border border-line-200 bg-card lg:block">
            <div class="grid grid-cols-7 border-b border-line-200">
                <div v-for="h in HEAD" :key="h" class="py-2 text-center font-mono text-[11px] uppercase text-ink-400">
                    {{ h }}
                </div>
            </div>
            <div class="grid grid-cols-7" style="grid-auto-rows: minmax(112px, auto)">
                <div
                    v-for="d in days"
                    :key="d.toISO()"
                    role="button"
                    tabindex="0"
                    class="cursor-pointer border-b border-r border-line-100 p-1.5 text-left align-top transition hover:bg-page-cream"
                    :class="d.isSameDay(today) ? 'bg-brand-50' : (!d.isSameMonth(anchor) ? 'bg-page-cream/60' : '')"
                    @click="emit('selectDay', d)"
                >
                    <div class="flex justify-end">
                        <span
                            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-[12px]"
                            :class="d.isSameDay(today)
                                ? 'bg-brand-700 font-semibold text-[#f4ead6]'
                                : (d.isSameMonth(anchor) ? 'text-ink-700' : 'text-ink-400')"
                        >
                            {{ d.day }}
                        </span>
                    </div>
                    <div class="mt-1 space-y-0.5">
                        <MonthChip
                            v-for="b in chipsFor(d).slice(0, 3)"
                            :key="b.id"
                            :block="b"
                            @click.stop="emit('select', b.id)"
                        />
                        <p v-if="chipsFor(d).length > 3" class="px-1 text-[10px] text-brand-700">
                            +{{ chipsFor(d).length - 3 }} más
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile: dot grid + selected-day list -->
        <div class="lg:hidden">
            <div class="overflow-hidden rounded-xl border border-line-200 bg-card">
                <div class="grid grid-cols-7 border-b border-line-200">
                    <div v-for="(h, i) in HEAD_SHORT" :key="i" class="py-1.5 text-center font-mono text-[10px] text-ink-400">
                        {{ h }}
                    </div>
                </div>
                <div class="grid grid-cols-7" style="grid-auto-rows: 46px">
                    <button
                        v-for="d in days"
                        :key="d.toISO()"
                        type="button"
                        class="flex flex-col items-center justify-center border-b border-r border-line-100 transition"
                        :class="d.toISO() === activeIso ? 'bg-brand-50 ring-1 ring-inset ring-brand-300' : ''"
                        @click="selectedIso = d.toISO()"
                    >
                        <span
                            class="inline-flex h-6 w-6 items-center justify-center rounded-full text-[12px]"
                            :class="d.isSameDay(today)
                                ? 'bg-brand-700 font-semibold text-[#f4ead6]'
                                : (d.isSameMonth(anchor) ? 'text-ink-700' : 'text-ink-400')"
                        >
                            {{ d.day }}
                        </span>
                        <div class="mt-0.5 flex h-1.5 items-center gap-0.5">
                            <span
                                v-for="b in chipsFor(d).slice(0, 3)"
                                :key="b.id"
                                class="h-1.5 w-1.5 rounded-full"
                                :style="{ background: statusStyle(b.status).bg, boxShadow: statusStyle(b.status).border !== 'transparent' ? 'inset 0 0 0 1px ' + statusStyle(b.status).border : 'none' }"
                            ></span>
                        </div>
                    </button>
                </div>
            </div>

            <div class="mt-3 overflow-hidden rounded-xl border border-line-200 bg-card">
                <div class="flex items-center gap-2 border-b border-line-100 bg-page-cream/50 px-4 py-2">
                    <span class="font-display text-[16px] text-ink-900">{{ weekdayLong(activeDate) }} {{ activeDate.day }}</span>
                    <span class="font-mono text-[11px] text-ink-400">{{ monthLong(activeDate) }}</span>
                    <span v-if="activeDate.isSameDay(today)" class="rounded-full bg-brand-50 px-1.5 py-0.5 text-[10px] text-brand-700">Hoy</span>
                </div>
                <div class="space-y-2 p-3">
                    <EmptyState v-if="activeRows.length === 0">No hay hábitos programados para este día.</EmptyState>
                    <div
                        v-for="b in activeRows"
                        :key="b.id"
                        class="-mx-1 flex cursor-pointer items-center gap-3 rounded-lg px-1 py-1 transition hover:bg-page-cream"
                        @click="emit('select', b.id)"
                    >
                        <span class="h-8 w-1.5 shrink-0 rounded-full" :style="{ background: accentColor(b.accent) }"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[13px] text-ink-900">{{ b.name }}</p>
                            <p class="truncate font-mono text-[11px] text-ink-500">
                                {{ b.rangeLabel }}<span v-if="b.overnight" class="opacity-60"> (+1 día)</span>
                            </p>
                        </div>
                        <StatusChip :status="b.status" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
