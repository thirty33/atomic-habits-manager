<script>
export default {
    name: 'AppSidebarHelloUserItem',
}
</script>

<script setup>
defineProps({
    text: {
        type: String,
        required: true,
    },
})
</script>

<template>
    <li class="flex items-center py-1">
        <span class="h-px flex-1 bg-line-200" aria-hidden="true"></span>
        <span class="px-2 font-mono text-[11px] uppercase tracking-wide text-ink-400">{{ text }}</span>
        <span class="h-px flex-1 bg-line-200" aria-hidden="true"></span>
    </li>
</template>
