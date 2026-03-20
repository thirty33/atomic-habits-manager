<script>
export default {
    name: 'ReportReflection',
}
</script>

<script setup>
import { inject } from 'vue';

const store = inject('reportStore');
</script>

<template>
    <div class="mb-6 bg-white rounded-xl shadow-sm p-5 space-y-4">
        <h2 class="text-base font-semibold text-gray-900">Reflexión del día</h2>

        <div>
            <label class="block text-sm text-gray-600 mb-2">¿Cómo te sentiste hoy?</label>
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="mood in store.state.moods"
                    :key="mood.value"
                    @click="store.updateReportField('mood', mood.value)"
                    class="px-3 py-1.5 rounded-full text-sm border transition-all"
                    :class="store.state.report?.mood === mood.value
                        ? 'bg-indigo-100 text-indigo-800 border-indigo-300 ring-2 ring-indigo-500'
                        : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                >
                    {{ mood.emoji }} {{ mood.label }}
                </button>
            </div>
        </div>

        <div>
            <label class="block text-sm text-gray-600 mb-1">Notas</label>
            <textarea
                :value="store.state.report?.notes ?? ''"
                @input="store.updateReportField('notes', $event.target.value)"
                placeholder="¿Algo que quieras anotar sobre este día?"
                rows="3"
                class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none"
            ></textarea>
        </div>
    </div>
</template>