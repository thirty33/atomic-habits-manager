<script setup>
import { computed } from 'vue';
import { STATE_ORDER, stateMeta, chipStyle } from '@/reports/domain/status.js';

const props = defineProps({
    selected: { type: String, default: 'pending' },
});
const emit = defineEmits(['select']);

const options = computed(() =>
    STATE_ORDER.map((status) => {
        const meta = stateMeta(status);
        return { status, label: meta.label, ...chipStyle(meta.variant) };
    })
);
</script>

<template>
    <div class="flex flex-wrap gap-1.5">
        <button
            v-for="opt in options"
            :key="opt.status"
            type="button"
            class="rounded-full px-2.5 py-1 text-[11px] font-medium transition"
            :class="selected === opt.status ? 'ring-2 ring-offset-1' : 'opacity-55 hover:opacity-100'"
            :style="selected === opt.status
                ? { background: opt.bg, color: opt.fg, '--tw-ring-color': opt.fg }
                : { background: 'rgb(var(--color-line-100))', color: 'rgb(var(--color-ink-500))' }"
            @click="emit('select', opt.status)"
        >
            {{ opt.label }}
        </button>
    </div>
</template>
