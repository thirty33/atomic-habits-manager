<script>
export default {
    name: 'AppDatatableDateColumn',
}
</script>

<script setup>
defineProps({
    value: {
        type: String,
    },
})
</script>

<template>
    <td class="text-sm font-medium text-gray-900 dark:text-white px-6 py-4">
        {{ value }}
    </td>
</template>
