<script setup>
import { computed } from 'vue';
import { STATE_ORDER, stateMeta } from '@/reports/domain/status.js';

const props = defineProps({
    editor: { type: Object, required: true },
    atomicIaUrl: { type: String, default: null },
});

const counts = computed(() => props.editor.counts.value);
const rows = computed(() =>
    STATE_ORDER.map((status) => ({
        status,
        label: stateMeta(status).label,
        rail: stateMeta(status).rail,
        n: counts.value[status] ?? 0,
    }))
);
</script>

<template>
    <div class="space-y-4 lg:sticky lg:top-[164px]">
        <div class="rounded-xl border border-line-200 bg-card p-4">
            <p class="mb-2 font-mono text-[11px] uppercase text-ink-400">Resumen por estado</p>
            <div v-for="r in rows" :key="r.status" class="flex items-center justify-between py-1 text-[12px]">
                <span class="inline-flex items-center gap-2 text-ink-700">
                    <span class="h-2.5 w-2.5 rounded-full" :style="{ background: r.rail }"></span>
                    {{ r.label }}
                </span>
                <span class="font-mono text-ink-900">{{ r.n }}</span>
            </div>
        </div>

        <div v-if="atomicIaUrl" class="rounded-xl bg-brand-800 p-5 text-[#f4ead6]">
            <p class="font-mono text-[11px] uppercase opacity-80">— Atomic IA</p>
            <p class="mt-1 text-[13px] leading-snug">Conversa con tu coach sobre el día y recibe ideas para mejorar tus hábitos.</p>
            <a :href="atomicIaUrl" class="mt-3 inline-flex items-center gap-1 text-[12px] font-medium underline">Conversar →</a>
        </div>
    </div>
</template>
