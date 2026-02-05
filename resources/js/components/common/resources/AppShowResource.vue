<script>
export default {
    name: "AppShowResource",
}
</script>

<script setup>
import useIconLoader from "@/composables/useIconLoader.js";

defineProps({
    model: Object,
    modalData: Object,
});

const { loadIcon } = useIconLoader();
</script>

<template>
    <div class="mt-4 pb-4 rounded-lg shadow-sm ring-1 ring-gray-900/5">
        <h2 class="pt-4 pb-0 ml-4">{{ modalData?.title }}</h2>
        <dl class="divide-y divide-gray-900/5 dark:divide-gray-500">
            <div
                v-for="(row, key) in modalData?.extra_data?.resource_detail_config"
                :key="`resource-detail-config-${row.name}-${key}`"
                class="px-4 py-4 sm:px-6"
            >
                <dt class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400 mb-1">
                    <component
                        v-if="row.icon"
                        :is="loadIcon(row.icon)"
                        class="w-4 h-4 inline-block mr-1"
                    />
                    {{ row.label }}
                </dt>

                <dd class="text-sm text-gray-900 dark:text-white">
                    {{ row.is_boolean ? (model[row.column_name] ? 'Sí' : 'No') : model[row.column_name] }}
                </dd>
            </div>
        </dl>
    </div>
</template>
