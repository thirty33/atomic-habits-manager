<script>
export default {
    name: 'ReportProgressBar',
}
</script>

<script setup>
import { inject } from 'vue';

const store = inject('reportStore');
</script>

<template>
    <div class="mt-3">
        <div class="flex justify-between text-xs text-gray-500 mb-1">
            <span>{{ store.progressSummary.value.reported }}/{{ store.progressSummary.value.total }} reportados</span>
            <span>{{ store.progressPercent.value }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div
                class="h-2 rounded-full transition-all duration-300"
                :class="store.isComplete.value ? 'bg-green-500' : 'bg-indigo-500'"
                :style="{ width: store.progressPercent.value + '%' }"
            ></div>
        </div>
    </div>
</template>