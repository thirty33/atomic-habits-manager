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
        <span class="flex-1 h-px bg-sidebar-separator" aria-hidden="true"></span>
        <span class="px-2 text-sm text-sidebar-muted">{{ text }}</span>
        <span class="flex-1 h-px bg-sidebar-separator" aria-hidden="true"></span>
    </li>
</template>
