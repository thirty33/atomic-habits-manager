<script>
export default {
    name: 'DashboardStreak',
}
</script>

<script setup>
defineProps({
    eyebrow: {
        type: String,
        default: '',
    },
    count: {
        type: Number,
        default: 0,
    },
    unit: {
        type: String,
        default: 'días',
    },
    record: {
        type: Boolean,
        default: false,
    },
    cells: {
        type: Array,
        default: () => [],
    },
    from: {
        type: String,
        default: '',
    },
    to: {
        type: String,
        default: '',
    },
    progress: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="rounded-xl border border-line-200 bg-card p-5">
        <div class="flex items-center justify-between">
            <div>
                <div class="font-mono text-[11px] tracking-[0.12em] uppercase text-ink-400">{{ eyebrow }}</div>
                <div class="font-display text-[44px] mt-1.5 leading-none text-ink-900">
                    {{ count }} <span class="text-[15px] text-ink-500">{{ unit }}</span>
                </div>
            </div>
            <div
                v-if="record"
                class="inline-flex items-center gap-[5px] px-2.5 py-[3px] rounded-full text-[11.5px] font-medium bg-brand-50 text-brand-800"
            >
                <span class="w-[5px] h-[5px] rounded-full bg-current"></span>Récord personal
            </div>
        </div>

        <div class="mt-5 grid gap-1" style="grid-template-columns: repeat(21, minmax(0, 1fr));">
            <div
                v-for="(cell, index) in cells"
                :key="index"
                class="aspect-square rounded-[3px]"
                :class="cell ? 'bg-brand-700' : 'bg-line-200'"
            ></div>
        </div>

        <div class="mt-3 flex items-center justify-between text-[11.5px] text-ink-500">
            <span class="font-mono">{{ from }}</span>
            <span>{{ progress }}</span>
            <span class="font-mono">{{ to }}</span>
        </div>
    </div>
</template>
