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
    <div class="mb-4 rounded-xl border border-line-200 bg-card p-4">
        <div class="grid grid-cols-2 gap-3 lg:flex lg:items-end lg:gap-4">
            <div
                v-for="field in fields"
                :key="`filter-field-${field.props.name}-${field.uuid}`"
                class="lg:flex-1"
            >
                <component
                    :is="findComponentByName(field.component, loadedComponents)"
                    v-bind="field.props"
                    v-model="filters[field.props.name]"
                    @update:modelValue="filter"
                />
            </div>
        </div>

        <div class="mt-3 flex items-center justify-end">
            <button
                type="button"
                class="inline-flex items-center px-3 py-2 rounded-lg text-[12.5px] font-medium text-ink-500 hover:bg-line-100 hover:text-ink-900 transition-colors"
                @click="resetFilters"
            >
                Limpiar filtros
            </button>
        </div>
    </div>
</template>
