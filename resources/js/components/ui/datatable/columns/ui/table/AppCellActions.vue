<script lang="ts">
export default { name: "AppCellActionsTable" };
</script>

<script setup lang="ts">
import type { CellViewModel } from "../../contracts/cell";

defineProps<{ vm: Extract<CellViewModel, { kind: "actions" }> }>();
const emit = defineEmits<{ actionDispatched: [action: unknown] }>();
</script>

<template>
    <td class="px-4 py-4 text-right">
        <div class="inline-flex items-center gap-2 justify-end">
            <button
                v-for="(action, index) in vm.actions"
                :key="`table-action-${index}`"
                :class="action.class"
                @click="emit('actionDispatched', action)"
            >
                {{ action.label }}
            </button>
        </div>
    </td>
</template>