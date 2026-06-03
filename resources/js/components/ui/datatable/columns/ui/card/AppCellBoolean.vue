<script lang="ts">
export default { name: "AppCellBooleanCard" };
</script>

<script setup lang="ts">
import type { CellViewModel } from "../../contracts/cell";

defineProps<{
    vm: Extract<CellViewModel, { kind: "boolean" }>;
    label?: string;
}>();
</script>

<template>
    <div class="flex items-center justify-between">
        <dt class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400">{{ label }}</dt>
        <dd class="text-sm">
            <span
                class="inline-flex items-center gap-1.5 px-2.5 py-[3px] rounded-full text-[11.5px] font-medium"
                :class="vm.value ? 'bg-success-2/10 text-success-2' : 'bg-line-100 text-ink-700'"
            >
                <span class="w-[5px] h-[5px] rounded-full bg-current"></span>
                {{ vm.value ? vm.trueLabel : vm.falseLabel }}
            </span>
        </dd>
    </div>
</template>