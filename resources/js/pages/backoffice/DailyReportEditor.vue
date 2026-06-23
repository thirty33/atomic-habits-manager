<script>
export default { name: 'DailyReportEditor' };
</script>

<script setup>
import { useReportEditor } from '@/reports/application/use-report-editor.js';
import { HttpReportGateway } from '@/reports/infrastructure/http/http-report-gateway.js';
import ReportEditHeader from '@/components/reports/ReportEditHeader.vue';
import ReflectionCard from '@/components/reports/ReflectionCard.vue';
import EntryCard from '@/components/reports/EntryCard.vue';
import SummaryAside from '@/components/reports/SummaryAside.vue';
import { AppSpinner } from '@/components/ui';

const props = defineProps({
    jsonUrl: { type: String, required: true },
    saveEntriesUrl: { type: String, required: true },
    updateReportUrl: { type: String, required: true },
    backUrl: { type: String, required: true },
    atomicIaUrl: { type: String, default: null },
});

const editor = useReportEditor(
    new HttpReportGateway({
        jsonUrl: props.jsonUrl,
        saveEntriesUrl: props.saveEntriesUrl,
        updateReportUrl: props.updateReportUrl,
    })
);
</script>

<template>
    <div class="px-4 py-4 lg:px-6 lg:py-6">
        <div v-if="editor.state.loading" class="flex justify-center py-20">
            <AppSpinner />
        </div>

        <template v-else-if="editor.state.report">
            <ReportEditHeader :editor="editor" :back-url="backUrl" />

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <div class="space-y-5 lg:col-span-8">
                    <ReflectionCard :editor="editor" />

                    <div class="flex items-center gap-3">
                        <span class="font-mono text-[11px] uppercase text-ink-400">Bloques del día</span>
                        <span class="h-px flex-1 bg-line-200"></span>
                    </div>

                    <div class="space-y-3">
                        <EntryCard
                            v-for="(entry, index) in editor.state.entries"
                            :key="entry.daily_report_entry_id ?? `new-${index}`"
                            :editor="editor"
                            :entry="entry"
                            :index="index"
                        />
                    </div>

                    <button
                        type="button"
                        class="w-full rounded-xl border-2 border-dashed border-line-200 py-3 text-[13px] text-ink-500 transition hover:border-brand-700 hover:text-brand-700"
                        @click="editor.addEntry()"
                    >
                        + Añadir actividad extra
                    </button>
                </div>

                <div class="lg:col-span-4">
                    <SummaryAside :editor="editor" :atomic-ia-url="atomicIaUrl" />
                </div>
            </div>
        </template>
    </div>
</template>
