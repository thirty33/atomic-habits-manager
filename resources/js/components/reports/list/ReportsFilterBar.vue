<script setup>
defineProps({
    filters: { type: Object, required: true },
    moods: { type: Array, default: () => [] },
});
const emit = defineEmits(['apply', 'clear']);

const inputClass =
    'w-full rounded-lg border border-line-200 bg-card px-3 py-2 text-[13px] text-ink-900 focus:border-brand-700 focus:outline-none focus:ring-1 focus:ring-brand-700';
</script>

<template>
    <div class="mb-4 grid grid-cols-1 gap-3 rounded-xl border border-line-200 bg-card p-3 sm:grid-cols-2 lg:grid-cols-12">
        <div class="lg:col-span-3">
            <label class="mb-1 block font-mono text-[10px] uppercase text-ink-400">Desde</label>
            <input v-model="filters.date_range_from" type="date" :class="[inputClass, 'font-mono']" />
        </div>
        <div class="lg:col-span-3">
            <label class="mb-1 block font-mono text-[10px] uppercase text-ink-400">Hasta</label>
            <input v-model="filters.date_range_to" type="date" :class="[inputClass, 'font-mono']" />
        </div>
        <div class="lg:col-span-3">
            <label class="mb-1 block font-mono text-[10px] uppercase text-ink-400">Estado de ánimo</label>
            <select v-model="filters.mood" :class="inputClass">
                <option value="">Todos</option>
                <option v-for="m in moods" :key="m.value" :value="m.value">{{ m.label }}</option>
            </select>
        </div>
        <div class="flex items-end gap-2 lg:col-span-3">
            <button type="button" class="rounded-lg border border-line-200 px-3 py-2 text-[12px] text-ink-700 hover:bg-page-cream" @click="emit('clear')">
                Limpiar
            </button>
            <button type="button" class="rounded-lg bg-brand-700 px-3 py-2 text-[12px] text-[#f4ead6] hover:bg-brand-800" @click="emit('apply')">
                Aplicar
            </button>
        </div>
    </div>
</template>
