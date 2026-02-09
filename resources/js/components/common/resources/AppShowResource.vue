<script>
export default {
    name: "AppShowResource",
}
</script>

<script setup>
import useIconLoader from "@/composables/useIconLoader.js";
import useGridLayout from "@/composables/useGridLayout.js";

defineProps({
    model: Object,
    modalData: Object,
});

const { loadIcon } = useIconLoader();
const { gridClass } = useGridLayout();

const dayLabels = {
    0: 'Dom', 1: 'Lun', 2: 'Mar', 3: 'Mié', 4: 'Jue', 5: 'Vie', 6: 'Sáb',
};

const formatValue = (row, data) => {
    if (!data) return '—';

    const value = data[row.column_name];

    if (value === null || value === undefined || value === '') return '—';
    if (row.is_boolean) return value ? 'Sí' : 'No';
    if (Array.isArray(value)) return value.map(d => dayLabels[d] || d).join(', ');

    return value;
};

const getSectionData = (section, model) => {
    if (!section.data_key) return model;
    return model[section.data_key];
};
</script>

<template>
    <div class="mt-4 pb-4 rounded-lg shadow-sm ring-1 ring-gray-900/5">
        <h2 class="pt-4 pb-0 ml-4">{{ modalData?.title }}</h2>

        <!-- Nuevo: secciones con grid -->
        <template v-if="modalData?.extra_data?.resource_detail_sections">
            <div
                v-for="(section, sIndex) in modalData.extra_data.resource_detail_sections"
                :key="`section-${sIndex}`"
                class="px-4 sm:px-6"
                :class="{ 'mt-4 pt-4 border-t border-gray-200 dark:border-gray-600': sIndex > 0 }"
            >
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">
                    {{ section.title }}
                </h3>

                <template v-if="!section.data_key || getSectionData(section, model)">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-x-6 gap-y-3">
                        <div
                            v-for="(row, rIndex) in section.lines"
                            :key="`section-${sIndex}-row-${rIndex}`"
                            :class="gridClass(row)"
                        >
                            <dt class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400 mb-1">
                                <component
                                    v-if="row.icon"
                                    :is="loadIcon(row.icon)"
                                    class="w-4 h-4 inline-block mr-1"
                                />
                                {{ row.label }}
                            </dt>
                            <dd class="text-sm text-gray-900 dark:text-white">
                                {{ formatValue(row, getSectionData(section, model)) }}
                            </dd>
                        </div>
                    </div>
                </template>

                <p v-else class="text-sm text-gray-400 italic">
                    Sin programación
                </p>
            </div>
        </template>

        <!-- Legacy: lista plana sin grid -->
        <template v-else>
            <dl class="divide-y divide-gray-900/5 dark:divide-gray-500">
                <div
                    v-for="(row, key) in modalData?.extra_data?.resource_detail_config"
                    :key="`resource-detail-config-${row.name}-${key}`"
                    class="px-4 py-4 sm:px-6"
                >
                    <dt class="text-xs font-medium text-gray-500 uppercase dark:text-gray-400 mb-1">
                        <component
                            v-if="row.icon"
                            :is="loadIcon(row.icon)"
                            class="w-4 h-4 inline-block mr-1"
                        />
                        {{ row.label }}
                    </dt>
                    <dd class="text-sm text-gray-900 dark:text-white">
                        {{ row.is_boolean ? (model[row.column_name] ? 'Sí' : 'No') : model[row.column_name] }}
                    </dd>
                </div>
            </dl>
        </template>
    </div>
</template>
