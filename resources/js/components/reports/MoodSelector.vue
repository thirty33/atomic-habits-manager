<script setup>
const props = defineProps({
    moods: { type: Array, required: true }, // [{ value, label, emoji }]
    selected: { type: String, default: null },
});
const emit = defineEmits(['select']);
</script>

<template>
    <div class="flex flex-wrap gap-2 lg:flex-nowrap">
        <button
            v-for="mood in moods"
            :key="mood.value"
            type="button"
            class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-[13px] transition lg:flex-col lg:gap-1"
            :class="selected === mood.value
                ? 'border-brand-700 bg-brand-50 text-brand-800 font-medium'
                : 'border-line-200 bg-card text-ink-500 hover:bg-page-cream'"
            @click="emit('select', mood.value)"
        >
            <span class="text-[18px] leading-none">{{ mood.emoji }}</span>
            <span>{{ mood.label }}</span>
        </button>
    </div>
</template>
