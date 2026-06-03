<script lang="ts">
export default { name: "AppCellActionsCard" };
</script>

<script setup lang="ts">
import type { CellViewModel } from "../../contracts/cell";

defineProps<{ vm: Extract<CellViewModel, { kind: "actions" }> }>();
const emit = defineEmits<{ actionDispatched: [action: unknown] }>();
</script>

<template>
    <div class="flex items-center gap-2 pt-3 mt-1 border-t border-gray-100 dark:border-gray-700">
        <button
            v-for="(action, index) in vm.actions"
            :key="`card-action-${index}`"
            :class="action.class"
            @click="emit('actionDispatched', action)"
        >
            {{ action.label }}
        </button>
    </div>
</template>