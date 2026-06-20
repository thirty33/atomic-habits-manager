<script setup>
import { STATUS, LEGEND, NOW_RING } from '@/calendar/domain/status.js';
</script>

<template>
    <div class="hidden xl:flex flex-wrap items-center gap-2.5">
        <span
            v-for="key in LEGEND"
            :key="key"
            class="inline-flex items-center gap-1 text-[11px] text-ink-500"
        >
            <span
                class="h-2.5 w-2.5 rounded-sm"
                :style="{
                    background: STATUS[key].bg,
                    boxShadow: STATUS[key].border !== 'transparent' ? 'inset 0 0 0 1px ' + STATUS[key].border : 'none',
                }"
            ></span>
            {{ STATUS[key].label }}
        </span>
        <span class="inline-flex items-center gap-1 text-[11px] text-ink-500">
            <span class="h-2.5 w-2.5 rounded-full" :style="{ boxShadow: '0 0 0 2px ' + NOW_RING }"></span>
            En curso
        </span>
    </div>
</template>
