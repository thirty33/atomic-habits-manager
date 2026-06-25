<script>
export default { name: 'SubscribeHeader' };
</script>

<script setup>
// Action zone for the backoffice header: an extensible right-aligned cluster
// whose primary action is "Subscríbete", linking to the Plans page. Reuses the
// redesigned global `.btn`/`.btn-primary` classes plus the new `.action-zone`/
// `.subscribe-btn` token-based classes in app.css. Mobile-compact via `lg`.
defineProps({
    plansUrl: {
        type: String,
        required: true,
    },
    // When true the label collapses to an icon-only button (mobile compact).
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="action-zone">
        <a
            :href="plansUrl"
            class="btn btn-primary subscribe-btn"
            :class="compact ? '!px-2.5 !py-2.5' : '!py-2.5'"
            :aria-label="compact ? 'Subscríbete' : null"
        >
            <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" stroke="none">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />
            </svg>
            <span v-if="!compact">Subscríbete</span>
        </a>
    </div>
</template>
