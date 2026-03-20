<script>
export default {
    name: 'ReportEntryCard',
}
</script>

<script setup>
import { computed, inject } from 'vue';
import { STATUS_CONFIG } from '@/constants/reportEntryStatus.js';

const props = defineProps({
    entry: { type: Object, required: true },
    index: { type: Number, required: true },
});

const store = inject('reportStore');

const habitSelectValue = computed(() => {
    if (props.entry.custom_activity !== null && props.entry.custom_activity !== undefined && !props.entry.habit_id) return 'free';
    if (props.entry.habit_id) return String(props.entry.habit_id);
    if (props.entry.habit_occurrence_id && props.entry.habit) return String(props.entry.habit.habit_id);
    return '';
});

const cardBorderColor = computed(() => props.entry.habit?.color ?? '#e5e7eb');

function update(field, value) {
    store.updateEntry(props.index, { [field]: value });
}

function onHabitChange(event) {
    const val = event.target.value;
    if (val === 'free') {
        store.updateEntry(props.index, { habit_id: null, habit_occurrence_id: null, custom_activity: '', habit: null });
    } else if (val) {
        const habit = store.state.habits.find(h => h.habit_id === Number(val));
        store.updateEntry(props.index, {
            habit_id: Number(val),
            habit_occurrence_id: null,
            custom_activity: null,
            habit: habit ?? null,
        });
    }
}

function errorFor(field) {
    const key = `entries.${props.index}.${field}`;
    return store.state.errors[key]?.[0] ?? null;
}
</script>

<template>
    <div
        class="bg-white rounded-xl shadow-sm border-l-4 overflow-hidden"
        :style="{ borderLeftColor: cardBorderColor }"
    >
        <div class="p-4 space-y-4">
            <!-- Header: Habit select + remove button -->
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <select
                        :value="habitSelectValue"
                        @change="onHabitChange"
                        class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                        <option value="" disabled>Seleccionar actividad...</option>
                        <optgroup label="Mis hábitos">
                            <option
                                v-for="habit in store.state.habits"
                                :key="habit.habit_id"
                                :value="String(habit.habit_id)"
                            >
                                {{ habit.name }}
                            </option>
                        </optgroup>
                        <option value="free">Otra actividad</option>
                    </select>
                    <p v-if="errorFor('habit_id')" class="mt-1 text-xs text-red-600">{{ errorFor('habit_id') }}</p>
                </div>

                <button
                    @click="store.removeEntry(index)"
                    class="flex-shrink-0 p-1.5 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-colors"
                    title="Eliminar entrada"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                </button>
            </div>

            <!-- Free activity name -->
            <div v-if="habitSelectValue === 'free'">
                <input
                    type="text"
                    :value="entry.custom_activity"
                    @input="update('custom_activity', $event.target.value)"
                    placeholder="Nombre de la actividad..."
                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                />
                <p v-if="errorFor('custom_activity')" class="mt-1 text-xs text-red-600">{{ errorFor('custom_activity') }}</p>
            </div>

            <!-- Time pickers -->
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">Inicio</label>
                    <input
                        type="time"
                        :value="entry.start_time"
                        @input="update('start_time', $event.target.value)"
                        class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <p v-if="errorFor('start_time')" class="mt-1 text-xs text-red-600">{{ errorFor('start_time') }}</p>
                </div>
                <span class="text-gray-400 mt-5">&rarr;</span>
                <div class="flex-1">
                    <label class="block text-xs text-gray-500 mb-1">Fin</label>
                    <input
                        type="time"
                        :value="entry.end_time"
                        @input="update('end_time', $event.target.value)"
                        class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <p v-if="errorFor('end_time')" class="mt-1 text-xs text-red-600">{{ errorFor('end_time') }}</p>
                </div>
            </div>

            <!-- Status toggles -->
            <div>
                <label class="block text-xs text-gray-500 mb-2">Estado</label>
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="(config, status) in STATUS_CONFIG"
                        :key="status"
                        @click="update('status', status)"
                        class="px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
                        :class="[
                            entry.status === status
                                ? `${config.color} ring-2 ${config.ring}`
                                : 'bg-white text-gray-500 border-gray-200 hover:bg-gray-50'
                        ]"
                    >
                        {{ config.emoji }} {{ config.label }}
                    </button>
                </div>
                <p v-if="errorFor('status')" class="mt-1 text-xs text-red-600">{{ errorFor('status') }}</p>
            </div>

            <!-- Notes -->
            <div>
                <textarea
                    :value="entry.notes"
                    @input="update('notes', $event.target.value)"
                    placeholder="Notas (opcional)..."
                    rows="2"
                    class="w-full rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none"
                ></textarea>
            </div>
        </div>
    </div>
</template>