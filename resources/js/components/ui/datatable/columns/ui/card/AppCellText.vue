<script lang="ts">
export default { name: "AppCellTextCard" };
</script>

<script setup lang="ts">
import type { CellViewModel } from "../../contracts/cell";

defineProps<{
    vm: Extract<CellViewModel, { kind: "text" }>;
    variant?: "title" | "field";
    label?: string;
}>();
</script>

<template>
    <!-- TITLE: the card promotes the primary column to a heading. -->
    <h3
        v-if="variant === 'title'"
        class="font-display text-[20px] leading-tight text-ink-900 mb-3 pb-2 border-b border-line-100"
    >
        {{ vm.value }}
    </h3>

    <!-- FIELD: label/value pair (markup identical to the current inline branch). -->
    <div v-else class="flex items-center justify-between">
        <dt class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ label }}</dt>
        <dd class="text-sm text-gray-900 dark:text-white">{{ vm.value }}</dd>
    </div>
</template>