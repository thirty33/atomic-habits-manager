<script lang="ts">
export default { name: "AppCellText" };
</script>

<script setup lang="ts">
import type { CellViewModel } from "../../contracts/cell";

defineProps<{ vm: Extract<CellViewModel, { kind: "text" }> }>();
</script>

<template>
    <td class="px-4 py-4 text-[14px] text-ink-900">
        {{ vm.value }}
    </td>
</template>