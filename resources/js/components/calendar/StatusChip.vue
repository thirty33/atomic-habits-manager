<script setup>
import { computed } from 'vue';
import { statusStyle } from '@/calendar/domain/status.js';

const props = defineProps({
    status: { type: String, required: true },
    small: { type: Boolean, default: false },
});

const style = computed(() => {
    const st = statusStyle(props.status);
    return {
        background: st.bg,
        color: st.fg,
        boxShadow: st.border !== 'transparent' ? 'inset 0 0 0 1px ' + st.border : 'none',
    };
});
const label = computed(() => statusStyle(props.status).label);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full font-medium"
        :class="small ? 'px-2 py-0.5 text-[10px]' : 'px-2.5 py-1 text-[11px]'"
        :style="style"
    >
        {{ label }}
    </span>
</template>
