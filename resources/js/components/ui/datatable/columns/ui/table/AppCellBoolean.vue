<script lang="ts">
export default { name: "AppCellBooleanTable" };
</script>

<script setup lang="ts">
import type { CellViewModel } from "../../contracts/cell";

defineProps<{ vm: Extract<CellViewModel, { kind: "boolean" }> }>();
</script>

<template>
    <td class="px-4 py-4">
        <span
            class="inline-flex items-center gap-1.5 px-2.5 py-[3px] rounded-full text-[11.5px] font-medium"
            :class="vm.value ? 'bg-success-2/10 text-success-2' : 'bg-line-100 text-ink-700'"
        >
            <span class="w-[5px] h-[5px] rounded-full bg-current"></span>
            {{ vm.value ? vm.trueLabel : vm.falseLabel }}
        </span>
    </td>
</template>