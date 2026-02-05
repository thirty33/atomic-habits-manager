<script>
export default {
    name: "AppDatatableCard",
}
</script>

<script setup>
defineProps({
    row: {
        type: Object,
        required: true,
    },
    columns: {
        type: Array,
        required: true,
    },
})

defineEmits(['actionDispatched'])

const isActionsColumn = (column) => !!column.actions
const isBooleanColumn = (column) => column.component === 'AppDatatableBooleanColumn'
</script>

<template>
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-4 dark:bg-gray-800 dark:border-gray-700">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-3 pb-2 border-b border-gray-100 dark:border-gray-700">
            {{ row[columns[0]?.key] }}
        </h3>

        <dl class="space-y-2">
            <template v-for="column in columns.slice(1)" :key="`card-field-${column.key}`">
                <div
                    v-if="!isActionsColumn(column)"
                    class="flex items-center justify-between"
                >
                    <dt class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                        {{ column.label }}
                    </dt>
                    <dd v-if="isBooleanColumn(column)" class="text-sm">
                        <span
                            :class="row[column.key]
                                ? 'bg-green-100 text-green-800'
                                : 'bg-red-100 text-red-800'"
                            class="px-2 py-0.5 text-xs font-semibold rounded-full"
                        >
                            {{ row[column.key] ? column.true_value : column.false_value }}
                        </span>
                    </dd>
                    <dd v-else class="text-sm text-gray-900 dark:text-white">
                        {{ row[column.key] }}
                    </dd>
                </div>

                <div
                    v-else
                    class="flex items-center gap-2 pt-3 mt-1 border-t border-gray-100 dark:border-gray-700"
                >
                    <button
                        v-for="(action, index) in column.actions"
                        :key="`card-action-${index}`"
                        :class="action.class"
                        @click="$emit('actionDispatched', action)"
                    >
                        {{ action.label }}
                    </button>
                </div>
            </template>
        </dl>
    </div>
</template>