<script>
export default { name: 'FeatureRow' };
</script>

<script setup>
// A single feature line on a plan card. `kind` drives the icon chip color via
// the token-based `.feat`/`.ic` classes in app.css.
defineProps({
    kind: {
        type: String,
        default: 'on', // on | off | star
    },
    label: {
        type: String,
        required: true,
    },
    pro: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div :class="['feat', kind === 'off' ? 'muted' : '']">
        <span :class="['ic', kind]">
            <!-- check -->
            <svg v-if="kind === 'on'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12" />
            </svg>
            <!-- x -->
            <svg v-else-if="kind === 'off'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
            </svg>
            <!-- zap (star/pro) -->
            <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
            </svg>
        </span>
        <span :class="['txt', pro ? 'pro' : '']">{{ label }}</span>
    </div>
</template>
