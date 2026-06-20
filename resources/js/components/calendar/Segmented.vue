<script setup>
const props = defineProps({
    view: { type: String, required: true },
});
const emit = defineEmits(['change']);

const tabs = [
    ['mes', 'Mes'],
    ['semana', 'Semana'],
    ['dia', 'Día'],
    ['lista', 'Lista'],
];
</script>

<template>
    <div class="inline-flex gap-0.5 rounded-md bg-line-100 p-0.5">
        <button
            v-for="[id, label] in tabs"
            :key="id"
            type="button"
            class="rounded px-2.5 py-1 text-[12px] transition"
            :class="view === id ? 'bg-card text-ink-900 font-medium shadow-sm' : 'text-ink-500 hover:text-ink-700'"
            @click="emit('change', id)"
        >
            {{ label }}
        </button>
    </div>
</template>
