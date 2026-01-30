<script>
export default {
    name: 'AppSidebarLinkItem',
}
</script>

<script setup>
import useIconLoader from "@/composables/useIconLoader.js";

defineProps({
    text: {
        type: String,
        required: true,
    },
    href: {
        type: String,
        required: true,
    },
    iconComponent: {
        type: String,
        required: true,
    },
    current: {
        type: Boolean,
        required: true,
    },
})

const { loadIcon } = useIconLoader();
</script>

<template>
    <li>
        <a
            :href="href"
            :class="[
                'flex items-center px-2 py-1.5 rounded-lg group',
                current
                    ? 'bg-sidebar-hover text-sidebar-active'
                    : 'text-sidebar-text hover:bg-sidebar-hover hover:text-sidebar-active'
            ]"
        >
            <component
                :is="loadIcon(iconComponent)"
                class="shrink-0 w-5 h-5 transition duration-75 text-sidebar-muted group-hover:text-sidebar-active"
                aria-hidden="true"
            />
            <span class="ms-3">{{ text }}</span>
        </a>
    </li>
</template>
