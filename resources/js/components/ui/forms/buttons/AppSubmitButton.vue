<script>
export default {
    name: "AppSubmitButton",
}
</script>

<script setup>
defineProps({
    loading: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
})
</script>

<template>
    <button
        type="submit"
        :disabled="disabled || loading"
        class="px-4 py-2 text-white bg-btn-primary hover:bg-btn-primary-hover focus:ring-4 focus:outline-none focus:ring-btn-primary/30 font-medium rounded-lg text-sm text-center transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <slot />
    </button>
</template>
