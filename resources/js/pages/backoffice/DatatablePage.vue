<script>
export default {
    name: 'DatatablePage',
}
</script>

<script setup>
import { ref } from "vue";
import DataProvider from '@/providers/DataProvider.js';
import useDataProvider from "@/composables/useDataProvider.js";
import useDatatable from "@/composables/useDatatable.js";
import useModal from "@/composables/useModal";
import { TableListTemplate } from "@/components/templates";
import { AppDatatableFilter } from "@/components/ui/datatable";
import { AppDatatableSkeleton } from "@/components/ui/skeletons";
import { AppSpinner, AppModal } from "@/components/ui";
import { AppErrorState } from "@/components/ui/states";
import { AppTable } from "@/components/common";
import {
    AppCreateResource, AppEditResource, AppExportResource,
    AppRemoveResource, AppShowResource,
} from "@/components/common/resources";
import AppModalSteps from "@/components/common/resources/nodes/ui/AppModalSteps.vue";
import { AppButtonWithHeroIcon } from "@/components/ui/buttons";

defineProps({
    jsonUrl: {
        type: String,
        required: true,
    },
});

const { dataProviderKey, updateDataProvider } = useDataProvider();
const { modalAction, modelSelected, openModal, closeModal } = useModal();

// Marca si el modal abierto cambió datos en el servidor. Permite refrescar la
// tabla al cerrar por X/fondo (los flujos multi-paso crean en un paso intermedio
// y pueden cerrarse sin llegar a emitir "processed").
const modalMutated = ref(false);

const onOpenModal = (action, model = null) => {
    modalMutated.value = false;
    openModal(action, model);
};

const onModalClose = () => {
    closeModal(() => {
        if (modalMutated.value) {
            updateDataProvider();
        }
    });
};

const columns = ref([]);

const initConfig = data => {
    columns.value = data?.table_columns || [];
};

const {
    filters, updateSorter, filterData, paginate,
} = useDatatable(columns);

const modalComponents = {
    create: AppCreateResource,
    edit: AppEditResource,
    remove: AppRemoveResource,
    export: AppExportResource,
    show: AppShowResource,
    editNode: AppModalSteps,
    createNode: AppModalSteps,
};
</script>

<template>
    <DataProvider
        :provider-key="dataProviderKey"
        :url="jsonUrl"
        :filters="filters"
        @refreshed="initConfig"
    >
        <template v-slot="{loading, data, error}">
            <template v-if="! error">
                <AppSpinner v-if="loading" />

                <AppDatatableSkeleton
                    v-if="loading && data?.skeleton"
                    :columns="columns"
                    :rows="10"
                />

                <TableListTemplate v-if="!loading">
                    <template v-slot:topActionButtons>
                        <AppButtonWithHeroIcon
                            v-for="button in data?.table_buttons"
                            :key="`top-action-button-${button.id}`"
                            :button="button"
                            @clicked="onOpenModal(button.action)"
                        />
                    </template>

                    <template v-slot:filters>
                        <AppDatatableFilter
                            :filters="filters"
                            :fields="data?.filter_fields"
                            @filter="filterData($event, () => updateDataProvider());"
                        />
                    </template>

                    <template v-slot:table>
                        <AppTable
                            :columns="columns"
                            :table-data="data?.table_data"
                            @sort="updateSorter($event, () => updateDataProvider());"
                            @paginate="paginate($event, () => updateDataProvider());"
                            @actionDispatched="onOpenModal($event.action, $event.model)"
                        />
                    </template>

                    <template v-slot:modals>
                        <AppModal
                            :opened="!!modalAction"
                            :max-width-class="data.modals[modalAction]?.max_width"
                            @close="onModalClose"
                        >
                            <template v-slot:content>
                                <component
                                    :is="modalComponents[modalAction]"
                                    :model="modelSelected"
                                    :modal-data="data.modals[modalAction]"
                                    @close="closeModal(() => {})"
                                    @processed="closeModal(() => updateDataProvider())"
                                    @mutated="modalMutated = true"
                                />
                            </template>
                        </AppModal>
                    </template>
                </TableListTemplate>
            </template>

            <AppErrorState
                v-else
                :error="error"
                @refresh="updateDataProvider"
            />
        </template>
    </DataProvider>
</template>
