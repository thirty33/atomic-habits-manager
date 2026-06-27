<script>
export default {
    name: 'BackofficeLayout',
}
</script>

<script setup>
import { computed } from 'vue'
import { AppSidebar } from '@/components/ui/sidebars'
import SubscribeHeader from '@/components/subscriptions/SubscribeHeader.vue'

const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    sidebarNavItems: {
        type: Array,
        required: true,
    },
    plansUrl: {
        type: String,
        default: '/plans',
    },
    currentPlanTier: {
        type: String,
        default: 'free',
    },
})

const isUnlimited = computed(() => props.currentPlanTier === 'unlimited')
const planLabel = computed(() => (isUnlimited.value ? 'Unlimited' : 'Free'))
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

            <div class="action-zone">
                <!-- Current plan badge -->
                <span :class="['chip', isUnlimited ? 'brand' : 'neutral']" title="Tu plan actual">
                    <span class="dot"></span>{{ planLabel }}
                </span>

                <!-- Subscríbete only when there's something to upgrade to -->
                <template v-if="!isUnlimited">
                    <SubscribeHeader :plans-url="plansUrl" class="hidden lg:flex" />
                    <SubscribeHeader :plans-url="plansUrl" compact class="lg:hidden" />
                </template>
            </div>
        </header>

        <main>
            <div class="mx-auto max-w-full sm:px-6 lg:px-8 py-4">
                <slot />
            </div>
        </main>
    </div>
</template>
