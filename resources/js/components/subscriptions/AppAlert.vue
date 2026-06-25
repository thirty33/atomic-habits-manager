<script>
export default { name: 'AppAlert' };
</script>

<script setup>
// Inline alert banner (AppAlert). Styled by the token-based `.app-alert`
// classes in app.css. Light-only.
defineProps({
    variant: {
        type: String,
        default: 'danger',
    },
    title: {
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
    <div :class="['app-alert', variant]" role="alert">
        <span class="mt-px shrink-0">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="16" x2="12" y2="12" />
                <line x1="12" y1="8" x2="12.01" y2="8" />
            </svg>
        </span>
        <div>
            <div v-if="title" class="font-medium">{{ title }}</div>
            <div v-if="message" :class="title ? 'opacity-90 mt-0.5' : ''">{{ message }}</div>
        </div>
    </div>
</template>
