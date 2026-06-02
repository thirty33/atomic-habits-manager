<script lang="ts">
export default { name: "AppNode", inheritAttrs: false };
</script>

<script setup lang="ts">
import { computed } from "vue";
import useComponentsLoader from "@/composables/useComponentsLoader.js";
import { NODE_COMPONENTS } from "../adapters/nodeComponents";
import type { ModalNode } from "../domain/node-schema";

const props = defineProps<{ node: ModalNode }>();
const { nodesLoader } = useComponentsLoader();
const resolved = computed(() => nodesLoader(NODE_COMPONENTS[props.node.kind]));
</script>

<template>
    <component :is="resolved" :node="node" v-bind="$attrs" />
</template>
