<script>
export default {
    name: 'AppStat',
}
</script>

<script setup>
defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: String,
        required: true,
    },
})
</script>

<template>
    <div
        class="flex flex-col items-center justify-center p-5 m-2 bg-white rounded-lg shadow border border-gray-200 dark:bg-gray-800 dark:border-gray-700"
    >
        <p class="text-sm font-medium tracking-wide text-gray-500 uppercase dark:text-gray-400">
            {{ label }}
        </p>
        <p class="mt-1 text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">
            <span class="inline-block">{{ value }}</span>
        </p>
    </div>
</template>
