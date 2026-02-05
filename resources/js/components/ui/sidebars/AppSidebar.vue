<script>
export default {
    name: 'AppSidebar',
}
</script>

<script setup>
import { ref } from 'vue';
import {
    AppSidebarHelloUserItem, AppSidebarLinkItem, AppSidebarSeparatorItem,
} from '@/components/ui/sidebars/items';

defineProps({
    sidebarNavItems: {
        type: Array,
        required: true,
    },
})

const sidebarComponents = {
    AppSidebarHelloUserItem,
    AppSidebarLinkItem,
    AppSidebarSeparatorItem,
}

const sidebarOpen = ref(false);
</script>

<template>
    <button
        type="button"
        class="fixed top-3 left-3 z-50 inline-flex items-center p-2 text-sidebar-muted bg-transparent border border-transparent rounded-lg hover:bg-sidebar-hover focus:ring-4 focus:ring-sidebar-border text-sm focus:outline-none lg:hidden"
        @click="sidebarOpen = !sidebarOpen"
    >
        <span class="sr-only">Open sidebar</span>
        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h10"/>
        </svg>
    </button>

    <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-30 bg-black/50 lg:hidden"
        @click="sidebarOpen = false"
    />

    <aside
        id="sidebar-multi-level-sidebar"
        :class="[
            'fixed top-0 left-0 z-40 w-64 h-screen transition-transform',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]"
        aria-label="Sidebar"
    >
        <div class="h-full px-3 py-4 pt-14 lg:pt-4 overflow-y-auto bg-sidebar-bg border-r border-sidebar-border">
            <ul class="space-y-2 font-medium">
                <component
                    v-for="(item, index) in sidebarNavItems"
                    :key="`sidebar-nav-item-${index}`"
                    :is="sidebarComponents[item?.component]"
                    v-bind="item?.props"
                />
            </ul>
        </div>
    </aside>
</template>
