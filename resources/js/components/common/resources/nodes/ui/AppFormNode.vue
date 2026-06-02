<script lang="ts">
export default { name: "AppFormNode" };
</script>

<script setup lang="ts">
import { computed } from "vue";
import useComponentsLoader from "@/composables/useComponentsLoader.js";
import useGridLayout from "@/composables/useGridLayout.js";
import type { FormNode, FormValues } from "../domain/node-schema";
import { visibleFields as computeVisibleFields } from "../domain/form";

const props = defineProps<{
    node: FormNode;
    form: FormValues;
    errorFor?: (name: string) => string | null;
    cssClassFor?: (name: string) => string;
}>();

const { findComponentByName, components, formFieldsLoader } = useComponentsLoader();
const { gridClass } = useGridLayout();

const loadedComponents = computed(() => components(props.node.fields, formFieldsLoader));
const visibleFields = computed(() => computeVisibleFields(props.node.fields, props.form));
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-x-6 gap-y-2">
        <div v-for="field in visibleFields" :key="field.uuid" :class="gridClass(field)">
            <component
                :is="findComponentByName(field.component, loadedComponents)"
                v-bind="field.props"
                :model-value="form[field.props.name]"
                :error="errorFor ? errorFor(field.props.name) : null"
                :css-class="cssClassFor ? cssClassFor(field.props.name) : ''"
                @update:model-value="form[field.props.name] = $event"
            />
        </div>
    </div>
</template>