<script setup>
import { computed } from 'vue';
import { chipStyle } from '@/reports/domain/status.js';

const props = defineProps({
    variant: { type: String, default: 'neutral' },
    dot: { type: Boolean, default: false },
    small: { type: Boolean, default: false },
});

const style = computed(() => {
    const c = chipStyle(props.variant);
    return { background: c.bg, color: c.fg };
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full font-medium"
        :class="small ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-1 text-[11px]'"
        :style="style"
    >
        <span v-if="dot" class="h-1.5 w-1.5 rounded-full bg-current"></span>
        <slot />
    </span>
</template>
