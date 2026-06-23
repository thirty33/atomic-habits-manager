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
                'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-[14px] transition',
                current
                    ? 'bg-brand-50 font-medium text-brand-800'
                    : 'text-ink-700 hover:bg-line-100 hover:text-ink-900'
            ]"
        >
            <span
                v-if="current"
                class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-brand-700"
                aria-hidden="true"
            ></span>
            <component
                :is="loadIcon(iconComponent)"
                :class="[
                    'h-5 w-5 shrink-0 transition duration-75',
                    current ? 'text-brand-700' : 'text-ink-400 group-hover:text-ink-700'
                ]"
                aria-hidden="true"
            />
            <span>{{ text }}</span>
        </a>
    </li>
</template>
