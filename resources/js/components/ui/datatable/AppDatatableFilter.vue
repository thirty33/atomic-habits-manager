<script>
export default {
    name: 'AppDatatableFilter',
}
</script>

<script setup>
import { reactive } from 'vue';
import useComponentsLoader from "@/composables/useComponentsLoader.js";

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({
            query: '',
        }),
    },
    fields: {
        type: Array,
        default: () => [],
    },
})

const emit = defineEmits(['filter']);

const filters = reactive(props.filters)

const filter = () => {
    emit('filter', filters);
}

const resetFilters = () => {
    Object.keys(filters).forEach((key) => {
        filters[key] = '';
    });
    filter();
}

const { findComponentByName, components, formFieldsLoader } = useComponentsLoader();
const loadedComponents = components(props.fields, formFieldsLoader);
</script>

<template>
    <div class="mb-4">
        <div class="grid grid-cols-2 gap-3 lg:flex lg:items-end lg:space-x-4">
            <div
                v-for="field in fields"
                :key="`filter-field-${field.props.name}-${field.uuid}`"
            >
                <component
                    :is="findComponentByName(field.component, loadedComponents)"
                    v-bind="field.props"
                    v-model="filters[field.props.name]"
                    @update:modelValue="filter"
                />
            </div>

            <button
                type="button"
                class="inline-flex items-center justify-center px-4 py-2 self-end text-white bg-btn-secondary hover:bg-btn-secondary-hover focus:ring-4 focus:outline-none focus:ring-btn-secondary/30 font-medium rounded-lg text-sm text-center transition-colors"
                @click="resetFilters"
            >
                Limpiar filtros
            </button>
        </div>
    </div>
</template>
