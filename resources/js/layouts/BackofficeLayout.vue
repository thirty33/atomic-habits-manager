<script>
export default {
    name: 'BackofficeLayout',
}
</script>

<script setup>
import { AppSidebar } from '@/components/ui/sidebars'

defineProps({
    title: {
        type: String,
        required: true,
    },
    sidebarNavItems: {
        type: Array,
        required: true,
    },
})
</script>

<template>
    <AppSidebar
        :sidebarNavItems="sidebarNavItems"
    />

    <div class="pt-14 lg:pt-4 p-4 lg:ml-64 bg-page-bg min-h-screen">
        <header
            class="flex items-center justify-between border-b border-page-border px-4 py-4 sm:px-6 sm:py-6 lg:px-8"
        >
            <h1 class="text-base font-semibold leading-7 text-page-heading">
                {{ title }}
            </h1>
        </header>

        <main>
            <div class="mx-auto max-w-full sm:px-6 lg:px-8 py-4">
                <slot />
            </div>
        </main>
    </div>
</template>
