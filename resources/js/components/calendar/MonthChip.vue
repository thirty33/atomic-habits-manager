<script setup>
import { computed } from 'vue';
import { statusStyle, accentColor } from '@/calendar/domain/status.js';

const props = defineProps({
    block: { type: Object, required: true },
});

const style = computed(() => {
    const st = statusStyle(props.block.status);
    return {
        background: st.bg,
        color: st.fg,
        boxShadow: st.border !== 'transparent' ? 'inset 0 0 0 1px ' + st.border : 'none',
    };
});
</script>

<template>
    <div class="flex cursor-pointer items-center gap-1 truncate rounded px-1.5 py-[3px] text-[11px] transition hover:brightness-95" :style="style">
        <span class="h-3 w-1 shrink-0 rounded-sm" :style="{ background: accentColor(block.accent) }"></span>
        <span class="shrink-0 font-mono text-[10px]">{{ block.startTime }}</span>
        <span class="truncate">{{ block.name }}</span>
        <span v-if="block.overnight" class="ml-auto shrink-0 font-mono text-[9px] opacity-70">+1d</span>
    </div>
</template>
