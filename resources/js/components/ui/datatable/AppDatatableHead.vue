<script>
export default {
    name: "AppDatatableHead",
}
</script>

<script setup>
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/20/solid'

defineProps({
    columns: {
        type: Array,
        default: () => [],
    },
})

defineEmits(['sort'])
</script>

<template>
    <thead>
        <tr class="border-b border-line-200">
            <th
                v-for="(column, index) in columns"
                :key='`column-${index}`'
                class="px-4 py-3.5 font-mono text-[10.5px] font-medium tracking-[0.1em] uppercase text-ink-400 text-left whitespace-nowrap"
                :class="{ 'cursor-pointer hover:text-ink-700': column.sortable }"
                @click="$emit('sort', column)"
            >
                <span class="inline-flex items-center gap-1">
                    {{ column.label }}
                    <span v-if="column.sortable">
                        <ChevronUpIcon
                            v-if="column.direction === 'asc'"
                            class="h-3 w-3"
                            aria-hidden="true"
                        />
                        <ChevronDownIcon
                            v-else
                            class="h-3 w-3"
                            aria-hidden="true"
                        />
                    </span>
                </span>
            </th>
        </tr>
    </thead>
</template>

