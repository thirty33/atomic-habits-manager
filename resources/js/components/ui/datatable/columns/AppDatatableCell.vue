<script lang="ts">
export default { name: "AppDatatableCell" };
</script>

<script setup lang="ts">
import { computed } from "vue";
import type { Component } from "vue";
import type { CellViewModel } from "./contracts/cell";
import { project, resolveRenderer, type ColumnConfig, type Viewport } from "./registry";

const props = defineProps<{
    column: ColumnConfig;
    row: Record<string, unknown>;
    viewport: Viewport;
    variant?: "title" | "field";
    label?: string;
}>();

const emit = defineEmits<{ actionDispatched: [action: unknown] }>();

// Stage 1 (the logical screen) is produced by the backend; here we only PROJECT the
// already-normalized row into the renderer's view-model (glue code, no derivation). The
// renderer never reads `row` directly — it receives `vm`.
const vm = computed<CellViewModel | null>(() => project(props.column.kind, props.row, props.column));
const renderer = computed<Component | null>(() => resolveRenderer(props.column.kind, props.viewport));
</script>

<template>
    <component
        :is="renderer"
        :vm="vm"
        :variant="variant"
        :label="label"
        @actionDispatched="emit('actionDispatched', $event)"
    />
</template>