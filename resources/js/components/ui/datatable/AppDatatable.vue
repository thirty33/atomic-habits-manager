<script>
export default {
    name: "AppDatatable",
}
</script>

<script setup>
import useComponentsLoader from "@/composables/useComponentsLoader.js";
import {
    AppDatatableTable,
    AppDatatableHead,
    AppDatatableBody,
    AppDatatableRow,
    AppDatatableNoData,
    AppDatatablePagination,
    AppDatatableCard,
} from "@/components/ui/datatable";
import AppDatatableCell from "@/components/ui/datatable/columns/AppDatatableCell.vue";
import { resolveRenderer } from "@/components/ui/datatable/columns/registry";

const props = defineProps({
    columns: {
        type: Array,
        default: () => [],
    },
    rows: {
        type: Array,
        default: () => [],
    },
    pagination: {
        type: Object,
    }
})

defineEmits(['sort', 'paginate', 'actionDispatched']);

const { findComponentByName, components, columnsLoader } = useComponentsLoader();
const loadedComponents = components(props.columns, columnsLoader);
</script>

<template>
    <div v-if="rows.length">
        <!-- Mobile & Tablet: Card view -->
        <div class="lg:hidden space-y-3">
            <AppDatatableCard
                v-for="(row, rowIndex) in rows"
                :key="`card-row-${rowIndex}`"
                :row="row"
                :columns="columns"
                @actionDispatched="$emit('actionDispatched', { type: $event.event, row })"
            />
        </div>

        <!-- Desktop: Table view -->
        <div class="hidden lg:block rounded-xl border border-line-200 bg-card overflow-hidden">
            <AppDatatableTable>
                <AppDatatableHead
                    :columns="columns"
                    @sort="$emit('sort', $event)"
                />
                <AppDatatableBody>
                    <AppDatatableRow
                        v-for="(row, rowIndex) in rows"
                        :key="`table-row-${rowIndex}`"
                    >
                        <template v-for="column in columns">
                            <AppDatatableCell
                                v-if="resolveRenderer(column.kind, 'table')"
                                :key="`cell-${column.key}-${rowIndex}`"
                                :column="column"
                                :row="row"
                                viewport="table"
                                @actionDispatched="$emit('actionDispatched', { type: $event.event, row })"
                            />

                            <component
                                v-else-if="column.is_compound"
                                :key="`table-compound-${column.key}-${rowIndex}`"
                                :is="findComponentByName(column.component, loadedComponents)"
                                :column="column"
                                :data="row[column.data_key]"
                            />

                            <component
                                v-else-if="!column.actions"
                                :key="`table-column-${column.key}-${rowIndex}`"
                                :is="findComponentByName(column.component, loadedComponents)"
                                :column="column"
                                :value="row[column.key]"
                            />

                            <component
                                v-else
                                :key="`table-action-column-${column.key}-${rowIndex}`"
                                :is="findComponentByName(column.component, loadedComponents)"
                                :actions="column.actions"
                                @actionDispatched="$emit('actionDispatched', { type: $event.event, row })"
                            />
                        </template>
                    </AppDatatableRow>
                </AppDatatableBody>
            </AppDatatableTable>

            <div class="border-t border-line-200">
                <AppDatatablePagination
                    :pagination="pagination"
                    @paginate="$emit('paginate', $event)"
                />
            </div>
        </div>

        <div class="lg:hidden mt-4 rounded-xl border border-line-200 bg-card overflow-hidden">
            <AppDatatablePagination
                :pagination="pagination"
                @paginate="$emit('paginate', $event)"
            />
        </div>
    </div>

    <AppDatatableNoData v-else />
</template>

<style scoped>
.table-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #d1d5db #f3f4f6;
}

.table-scrollbar::-webkit-scrollbar {
    height: 8px;
}

.table-scrollbar::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 4px;
}

.table-scrollbar::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 4px;
}

.table-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #9ca3af;
}
</style>

