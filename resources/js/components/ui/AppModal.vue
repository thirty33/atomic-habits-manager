<script>
export default {
    name: 'AppModal',
}
</script>

<script setup>
defineProps({
    opened: {
        type: Boolean,
        default: false,
    },
    maxWidthClass: {
        type: String,
        default: 'max-w-2xl',
    },
})

defineEmits(['close'])
</script>

<template>
    <div v-show="opened">
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto bg-ink-900/45">
            <div class="flex min-h-full items-end justify-center p-4 sm:items-center sm:p-0">
                <div :class="['relative p-4 w-full max-h-full', maxWidthClass]">
                    <div class="relative bg-card rounded-2xl border border-line-200 shadow-[0_30px_80px_-20px_rgba(0,0,0,0.3)]">
                        <div class="absolute right-0 top-0 pr-3.5 pt-3.5 z-10">
                            <button
                                type="button"
                                class="text-ink-400 bg-transparent hover:bg-line-100 hover:text-ink-900 rounded-md text-sm w-8 h-8 ms-auto inline-flex justify-center items-center transition-colors"
                                @click="$emit('close')"
                            >
                                <svg
                                    class="w-3 h-3"
                                    aria-hidden="true"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 14 14"
                                >
                                    <path
                                        stroke="currentColor"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"
                                    />
                                </svg>
                            </button>
                        </div>

                        <slot name="content" />

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
