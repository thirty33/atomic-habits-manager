<script>
export default {
    name: 'AppDatatableSchedulesCompoundColumn',
}
</script>

<script setup>
import { computed } from 'vue';
import useScheduleDisplay from "@/composables/useScheduleDisplay.js";

const props = defineProps({
    column: {
        type: Object,
    },
    data: {
        type: Array,
        default: () => [],
    },
});

const MAX_VISIBLE = 3;

const { getExtraInfo } = useScheduleDisplay();

const schedules = computed(() => Array.isArray(props.data) ? props.data : []);
const visible = computed(() => schedules.value.slice(0, MAX_VISIBLE));
const hiddenCount = computed(() => Math.max(0, schedules.value.length - MAX_VISIBLE));
</script>

<template>
    <td class="text-sm px-6 py-4 align-top">
        <ul v-if="schedules.length" class="space-y-1.5">
            <li
                v-for="(item, index) in visible"
                :key="item.habit_schedule_id ?? index"
                class="flex items-baseline gap-2"
            >
                <span class="inline-flex shrink-0 items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-brand-50 text-brand-800 ring-1 ring-inset ring-brand-100">
                    {{ item.recurrence_type_label }}
                </span>
                <span class="font-mono text-[12.5px] text-ink-900 whitespace-nowrap">
                    {{ item.start_time }} – {{ item.end_time }}
                </span>
                <span v-if="getExtraInfo(item)" class="text-[11.5px] text-ink-500 truncate">
                    {{ getExtraInfo(item) }}
                </span>
            </li>
            <li v-if="hiddenCount" class="text-[11.5px] text-ink-400 font-mono">
                +{{ hiddenCount }} más
            </li>
        </ul>
        <span v-else class="text-ink-400 italic text-xs">Sin programación</span>
    </td>
</template>