<script>
export default {
    name: 'DashboardAtomicInsight',
}
</script>

<script setup>
defineProps({
    eyebrow: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="rounded-xl bg-brand-800 text-paper p-6 relative overflow-hidden">
        <div
            class="absolute inset-0 opacity-[0.08] pointer-events-none"
            style="background-image: radial-gradient(circle, rgb(var(--color-paper)) 1px, transparent 1px); background-size: 18px 18px;"
        ></div>

        <div class="relative flex items-center gap-2 mb-2">
            <span class="inline-grid place-items-center w-[22px] h-[22px] rounded-md bg-paper text-brand-800 font-display text-[13px] leading-none pb-[2px]">A</span>
            <span class="font-mono text-[10.5px] tracking-[0.12em] uppercase opacity-70">{{ eyebrow }}</span>
        </div>

        <p class="relative font-display text-[22px] leading-snug">{{ message }}</p>
    </div>
</template>
