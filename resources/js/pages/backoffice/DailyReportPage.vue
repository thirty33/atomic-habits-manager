<script>
export default {
    name: 'DailyReportPage',
}
</script>

<script setup>
import { onMounted, onBeforeUnmount, provide } from 'vue';
import useReportStore from '@/composables/useReportStore.js';
import ReportHeader from '@/components/daily-reports/ReportHeader.vue';
import ReportEntryList from '@/components/daily-reports/ReportEntryList.vue';
import ReportReflection from '@/components/daily-reports/ReportReflection.vue';
import { AppSpinner } from '@/components/ui';

const props = defineProps({
    jsonUrl: { type: String, required: true },
    saveEntriesUrl: { type: String, required: true },
    updateReportUrl: { type: String, required: true },
    backUrl: { type: String, required: true },
});

const store = useReportStore(props.jsonUrl, props.saveEntriesUrl, props.updateReportUrl);
provide('reportStore', store);

onMounted(() => {
    store.setup();
    store.loadData();
});

onBeforeUnmount(() => {
    store.teardown();
});
</script>

<template>
    <div class="max-w-3xl mx-auto">
        <AppSpinner v-if="store.state.loading" />

        <template v-if="!store.state.loading && store.state.report">
            <ReportHeader :back-url="backUrl" />
            <ReportReflection />
            <ReportEntryList />
        </template>
    </div>
</template>