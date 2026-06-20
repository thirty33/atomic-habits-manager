<script setup>
import Segmented from './Segmented.vue';
import Legend from './Legend.vue';

defineProps({
    view: { type: String, required: true },
    title: { type: String, required: true },
    loading: { type: Boolean, default: false },
});
const emit = defineEmits(['prev', 'next', 'today', 'change']);
</script>

<template>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line-200 bg-card p-3">
        <div class="flex items-center gap-2">
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-line-200 text-lg leading-none text-ink-700 hover:bg-page-cream"
                aria-label="Anterior"
                @click="emit('prev')"
            >
                ‹
            </button>
            <button
                type="button"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-line-200 text-lg leading-none text-ink-700 hover:bg-page-cream"
                aria-label="Siguiente"
                @click="emit('next')"
            >
                ›
            </button>
            <button
                type="button"
                class="rounded-lg border border-line-200 px-3 py-1.5 text-[12px] text-ink-700 hover:bg-page-cream"
                @click="emit('today')"
            >
                Hoy
            </button>
            <h2 class="ml-1 font-display text-[22px] leading-none text-ink-900">{{ title }}</h2>
            <span v-if="loading" class="font-mono text-[10px] text-ink-400">cargando…</span>
        </div>
        <div class="flex items-center gap-3">
            <Legend />
            <Segmented :view="view" @change="(v) => emit('change', v)" />
        </div>
    </div>
</template>
