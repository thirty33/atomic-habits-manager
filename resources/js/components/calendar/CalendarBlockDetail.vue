<script setup>
import { computed, onMounted, onUnmounted } from 'vue';
import StatusChip from './StatusChip.vue';
import { accentColor } from '@/calendar/domain/status.js';
import { CalendarDate, weekdayLong, monthLong } from '@/calendar/domain/calendar-date.js';

const props = defineProps({
    block: { type: Object, required: true },
});
const emit = defineEmits(['close']);

const dateLabel = computed(() => {
    const d = CalendarDate.parse(props.block.date);
    return `${weekdayLong(d)} ${d.day} de ${monthLong(d)} ${d.year}`;
});

const timeLabel = computed(() =>
    props.block.overnight
        ? `${props.block.startTime} → ${props.block.endTime} (+1 día)`
        : `${props.block.startTime} – ${props.block.endTime}`
);

function onKey(e) {
    if (e.key === 'Escape') {
        emit('close');
    }
}
onMounted(() => document.addEventListener('keydown', onKey));
onUnmounted(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background: rgb(var(--color-ink-900) / 0.45)"
        @click.self="emit('close')"
    >
        <div class="w-full max-w-md overflow-hidden rounded-2xl border border-line-200 bg-card shadow-xl">
            <div class="flex items-start justify-between gap-3 border-b border-line-100 px-5 py-4">
                <div class="flex min-w-0 items-center gap-2.5">
                    <span class="h-8 w-1.5 shrink-0 rounded-full" :style="{ background: accentColor(block.accent) }"></span>
                    <div class="min-w-0">
                        <p class="font-mono text-[11px] uppercase text-ink-400">Programación</p>
                        <h3 class="truncate font-display text-[20px] text-ink-900">{{ block.name }}</h3>
                    </div>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1 text-ink-400 hover:bg-page-cream hover:text-ink-700"
                    aria-label="Cerrar"
                    @click="emit('close')"
                >
                    ✕
                </button>
            </div>

            <div class="space-y-3 px-5 py-4">
                <div class="flex items-center justify-between gap-4">
                    <span class="text-[12px] text-ink-500">Estado</span>
                    <StatusChip :status="block.status" />
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-[12px] text-ink-500">Fecha</span>
                    <span class="text-right text-[13px] text-ink-900">{{ dateLabel }}</span>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <span class="text-[12px] text-ink-500">Hora</span>
                    <span class="font-mono text-[13px] text-ink-900">{{ timeLabel }}</span>
                </div>
                <div v-if="block.natureLabel" class="flex items-center justify-between gap-4">
                    <span class="text-[12px] text-ink-500">Naturaleza</span>
                    <span class="text-right text-[13px] text-ink-900">{{ block.natureLabel }}</span>
                </div>
                <div v-if="block.desireLabel" class="flex items-center justify-between gap-4">
                    <span class="text-[12px] text-ink-500">Motivación</span>
                    <span class="text-right text-[13px] text-ink-900">{{ block.desireLabel }}</span>
                </div>
            </div>

            <div class="border-t border-line-100 bg-page-cream/60 px-5 py-3 text-right">
                <button
                    type="button"
                    class="rounded-lg border border-line-200 px-3.5 py-1.5 text-[13px] text-ink-700 hover:bg-card"
                    @click="emit('close')"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</template>
