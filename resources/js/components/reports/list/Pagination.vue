<script setup>
defineProps({
    meta: { type: Object, required: true },
});
const emit = defineEmits(['go']);
</script>

<template>
    <div v-if="meta.total > 0" class="mt-4 flex items-center justify-between">
        <span class="font-mono text-[11px] text-ink-400">{{ meta.from }}–{{ meta.to }} de {{ meta.total }}</span>
        <div class="flex items-center gap-1">
            <button
                type="button"
                class="rounded-lg border border-line-200 px-2.5 py-1.5 text-[12px] text-ink-700 hover:bg-page-cream disabled:opacity-40"
                :disabled="meta.current_page <= 1"
                @click="emit('go', meta.current_page - 1)"
            >
                ‹
            </button>
            <span class="px-2 font-mono text-[12px] text-ink-700">{{ meta.current_page }} / {{ meta.last_page }}</span>
            <button
                type="button"
                class="rounded-lg border border-line-200 px-2.5 py-1.5 text-[12px] text-ink-700 hover:bg-page-cream disabled:opacity-40"
                :disabled="meta.current_page >= meta.last_page"
                @click="emit('go', meta.current_page + 1)"
            >
                ›
            </button>
        </div>
    </div>
</template>
