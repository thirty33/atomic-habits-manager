<script>
export default {
    name: 'DashboardWeekAdherence',
}
</script>

<script setup>
defineProps({
    eyebrow: {
        type: String,
        default: '',
    },
    average: {
        type: Number,
        default: 0,
    },
    ranges: {
        type: Array,
        default: () => [],
    },
    days: {
        type: Array,
        default: () => [],
    },
    note: {
        type: String,
        default: '',
    },
    linkLabel: {
        type: String,
        default: '',
    },
});

const barClass = (day) => {
    if (day.future) {
        return 'bg-line-100 border-2 border-dashed border-line-200';
    }

    return day.warning ? 'bg-warning' : 'bg-brand-700';
};
</script>

<template>
    <div class="rounded-xl border border-line-200 bg-card p-5">
        <div class="flex items-center justify-between mb-1">
            <div>
                <div class="font-mono text-[11px] tracking-[0.12em] uppercase text-ink-400">{{ eyebrow }}</div>
                <div class="font-display text-[22px] mt-1 text-ink-900">
                    {{ average }}<span class="text-[14px] text-ink-500">% media</span>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <button
                    v-for="(range, index) in ranges"
                    :key="index"
                    type="button"
                    class="inline-flex items-center px-2.5 py-[3px] rounded-full text-[11px] font-medium"
                    :class="range.active ? 'bg-brand-50 text-brand-800' : 'bg-line-100 text-ink-700'"
                >
                    {{ range.label }}
                </button>
            </div>
        </div>

        <div class="mt-5 flex items-stretch gap-3 h-[140px]">
            <div v-for="(day, index) in days" :key="index" class="flex-1 flex flex-col items-center gap-2">
                <div class="w-full relative flex-1 flex items-end">
                    <div class="absolute inset-0 rounded-md bg-line-100"></div>
                    <div class="relative w-full rounded-md" :class="barClass(day)" :style="{ height: (day.value || 2) + '%' }"></div>
                    <span
                        v-if="!day.future"
                        class="absolute -top-5 left-1/2 -translate-x-1/2 font-mono text-[10px] text-ink-500"
                    >{{ day.value }}%</span>
                </div>
                <span class="font-mono text-[10.5px] text-ink-400 uppercase">{{ day.label }}</span>
            </div>
        </div>

        <div class="mt-3 flex items-center justify-between text-[11.5px] text-ink-500">
            <span>{{ note }}</span>
            <a href="#" class="text-brand-700 uline">{{ linkLabel }}</a>
        </div>
    </div>
</template>
