<script>
export default {
    name: 'DashboardPage',
}
</script>

<script setup>
import DataProvider from '@/providers/DataProvider.js';
import useDataProvider from "@/composables/useDataProvider.js";
import { AppSpinner } from "@/components/ui/index.js";
import {
    DashboardKpiCard,
    DashboardTodayTimeline,
    DashboardWeekAdherence,
    DashboardStreak,
    DashboardAtomicInsight,
    DashboardRecentReflection,
} from "@/components/backoffice/dashboard/index.js";

defineProps({
    jsonUrl: {
        type: String,
        required: true,
    },
})

const { dataProviderKey } = useDataProvider();
</script>

<template>
    <DataProvider
        :provider-key="dataProviderKey"
        :url="jsonUrl"
    >
        <template v-slot="{loading, error, data}">
            <AppSpinner v-if="loading" />

            <template v-if="!loading && !error">
                <!-- Header -->
                <header>
                    <div class="font-mono text-[11px] tracking-[0.12em] uppercase text-ink-400">
                        {{ data?.header?.eyebrow }}
                    </div>
                    <h1 class="display text-[40px] lg:text-[52px] text-ink-900 mt-2">
                        Buen día, <em class="italic text-brand-700">{{ data?.header?.greeting_name }}</em>.
                    </h1>
                    <p class="mt-3 text-[14px] text-ink-500 max-w-[560px] leading-relaxed">
                        {{ data?.header?.subtitle }}
                    </p>
                </header>

                <!-- KPIs -->
                <div class="mt-8 grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <DashboardKpiCard
                        v-for="(kpi, index) in data?.kpis?.items"
                        :key="index"
                        :label="kpi.label"
                        :value="kpi.value"
                        :delta="kpi.delta"
                        :sublabel="kpi.sublabel"
                    />
                </div>

                <!-- Main grid -->
                <div class="mt-6 grid grid-cols-1 lg:grid-cols-12 gap-4">
                    <div class="lg:col-span-8 space-y-4">
                        <DashboardTodayTimeline
                            :eyebrow="data?.today_timeline?.eyebrow"
                            :title="data?.today_timeline?.title"
                            :summary="data?.today_timeline?.summary"
                            :rows="data?.today_timeline?.rows"
                        />
                        <DashboardWeekAdherence
                            :eyebrow="data?.week_adherence?.eyebrow"
                            :average="data?.week_adherence?.average"
                            :ranges="data?.week_adherence?.ranges"
                            :days="data?.week_adherence?.days"
                            :note="data?.week_adherence?.note"
                            :link-label="data?.week_adherence?.link_label"
                        />
                    </div>
                    <div class="lg:col-span-4 space-y-4">
                        <DashboardAtomicInsight
                            :eyebrow="data?.atomic_insight?.eyebrow"
                            :message="data?.atomic_insight?.message"
                        />
                        <DashboardStreak
                            :eyebrow="data?.streak?.eyebrow"
                            :count="data?.streak?.count"
                            :unit="data?.streak?.unit"
                            :record="data?.streak?.record"
                            :cells="data?.streak?.cells"
                            :from="data?.streak?.from"
                            :to="data?.streak?.to"
                            :progress="data?.streak?.progress"
                        />
                        <DashboardRecentReflection
                            :eyebrow="data?.recent_reflection?.eyebrow"
                            :mood="data?.recent_reflection?.mood"
                            :text="data?.recent_reflection?.text"
                            :datetime="data?.recent_reflection?.datetime"
                            :link-label="data?.recent_reflection?.link_label"
                            :report-link="data?.recent_reflection?.report_link"
                        />
                    </div>
                </div>
            </template>
        </template>
    </DataProvider>
</template>
