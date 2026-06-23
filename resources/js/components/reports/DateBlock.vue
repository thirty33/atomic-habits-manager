<script setup>
import { computed } from 'vue';

const props = defineProps({
    date: { type: String, required: true }, // 'Y-m-d'
    size: { type: String, default: 'sm' }, // sm | lg
});

const parts = computed(() => {
    const [y, m, day] = props.date.split('-').map(Number);
    const dt = new Date(y, m - 1, day);
    const fmt = (opt) => new Intl.DateTimeFormat('es-ES', opt).format(dt).replace('.', '');
    return { day, month: fmt({ month: 'short' }), weekday: fmt({ weekday: 'short' }) };
});
</script>

<template>
    <div
        class="flex shrink-0 flex-col items-center justify-center rounded-xl border border-line-200 bg-paper"
        :class="size === 'lg' ? 'h-20 w-20' : 'h-12 w-12'"
    >
        <span class="font-mono uppercase text-ink-400" :class="size === 'lg' ? 'text-[11px]' : 'text-[9px]'">
            {{ parts.month }}
        </span>
        <span class="font-display leading-none text-ink-900" :class="size === 'lg' ? 'text-[34px]' : 'text-[18px]'">
            {{ parts.day }}
        </span>
        <span v-if="size === 'lg'" class="font-mono text-[10px] text-ink-400">{{ parts.weekday }}</span>
    </div>
</template>
