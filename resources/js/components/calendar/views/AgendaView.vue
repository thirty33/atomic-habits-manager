<script setup>
import { computed } from 'vue';
import StatusChip from '../StatusChip.vue';
import EmptyState from '../EmptyState.vue';
import { accentColor } from '@/calendar/domain/status.js';
import { weekDays } from '@/calendar/domain/navigation.js';
import { weekdayLong, monthShort } from '@/calendar/domain/calendar-date.js';
import { blocksOnDay } from '@/calendar/domain/calendar-block.js';

const props = defineProps({
    blocks: { type: Array, required: true },
    anchor: { type: Object, required: true },
    today: { type: Object, required: true },
});
const emit = defineEmits(['select']);

const days = computed(() => weekDays(props.anchor));
const rowsFor = (d) => blocksOnDay(props.blocks, d.toISO());
</script>

<template>
    <div class="space-y-4">
        <div v-for="d in days" :key="d.toISO()" class="overflow-hidden rounded-xl border border-line-200 bg-card">
            <div class="flex items-center justify-between border-b border-line-100 bg-page-cream/50 px-4 py-2">
                <div class="flex items-baseline gap-2">
                    <span class="font-display text-[16px] text-ink-900">{{ weekdayLong(d) }} {{ d.day }}</span>
                    <span class="font-mono text-[11px] text-ink-400">{{ monthShort(d) }}</span>
                    <span v-if="d.isSameDay(today)" class="rounded-full bg-brand-50 px-1.5 py-0.5 text-[10px] text-brand-700">Hoy</span>
                </div>
                <span class="font-mono text-[11px] text-ink-400">{{ rowsFor(d).length }} bloques</span>
            </div>
            <div class="space-y-2 p-3">
                <EmptyState v-if="rowsFor(d).length === 0">No hay hábitos programados para este día.</EmptyState>
                <div
                    v-for="b in rowsFor(d)"
                    :key="b.id"
                    class="-mx-1 flex cursor-pointer items-center gap-3 rounded-lg px-1 py-1 transition hover:bg-page-cream"
                    @click="emit('select', b.id)"
                >
                    <span class="hidden w-32 shrink-0 font-mono text-[12px] text-ink-500 lg:block">
                        {{ b.rangeLabel }}<span v-if="b.overnight" class="opacity-60"> (+1 día)</span>
                    </span>
                    <span class="h-9 w-1.5 shrink-0 rounded-full" :style="{ background: accentColor(b.accent) }"></span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] text-ink-900">{{ b.name }}</p>
                        <p class="truncate font-mono text-[11px] text-ink-500 lg:hidden">
                            {{ b.rangeLabel }}<span v-if="b.overnight" class="opacity-60"> (+1 día)</span>
                        </p>
                        <p v-if="b.sub" class="hidden truncate text-[11px] text-ink-400 lg:block">{{ b.sub }}</p>
                    </div>
                    <StatusChip :status="b.status" />
                </div>
            </div>
        </div>
    </div>
</template>
