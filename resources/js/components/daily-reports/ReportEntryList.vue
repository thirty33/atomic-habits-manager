<script>
export default {
    name: 'ReportEntryList',
}
</script>

<script setup>
import { inject } from 'vue';
import ReportEntryCard from '@/components/daily-reports/ReportEntryCard.vue';

const store = inject('reportStore');
</script>

<template>
    <div>
        <div class="space-y-4 mb-6">
            <ReportEntryCard
                v-for="(entry, index) in store.state.entries"
                :key="entry.daily_report_entry_id ?? `new-${index}`"
                :entry="entry"
                :index="index"
            />
        </div>

        <button
            @click="store.addEntry()"
            class="w-full py-3 border-2 border-dashed border-gray-300 rounded-xl text-sm text-gray-500 hover:border-indigo-400 hover:text-indigo-600 transition-colors"
        >
            + Añadir actividad
        </button>
    </div>
</template>