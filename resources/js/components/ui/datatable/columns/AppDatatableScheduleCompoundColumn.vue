<script>
export default {
    name: 'AppDatatableScheduleCompoundColumn',
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
        type: Object,
        default: null,
    },
});

const { getExtraInfo } = useScheduleDisplay();
const extraInfo = computed(() => getExtraInfo(props.data));
</script>

<template>
    <td class="text-sm px-6 py-4">
        <div v-if="data" class="space-y-0.5">
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                {{ data.recurrence_type_label }}
            </span>
            <p class="text-gray-700 dark:text-gray-300 text-xs">
                {{ data.start_time }} – {{ data.end_time }}
            </p>
            <p v-if="extraInfo" class="text-gray-500 dark:text-gray-400 text-xs">
                {{ extraInfo }}
            </p>
        </div>
        <span v-else class="text-gray-400 italic text-xs">Sin programación</span>
    </td>
</template>
