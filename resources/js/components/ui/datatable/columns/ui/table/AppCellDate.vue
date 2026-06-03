<script lang="ts">
export default { name: "AppCellDateTable" };
</script>

<script setup lang="ts">
import type { CellViewModel } from "../../contracts/cell";

defineProps<{ vm: Extract<CellViewModel, { kind: "text" }> }>();
</script>

<template>
    <td class="px-4 py-4">
        <span class="font-mono text-[13px] text-ink-500">{{ vm.value }}</span>
    </td>
</template>
