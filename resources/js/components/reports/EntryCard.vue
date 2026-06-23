<script setup>
import { computed, ref } from 'vue';
import ReportChip from './ReportChip.vue';
import StateSelector from './StateSelector.vue';
import { stateMeta } from '@/reports/domain/status.js';
import { entryDurationLabel } from '@/reports/domain/report-entry.js';

const props = defineProps({
    editor: { type: Object, required: true },
    entry: { type: Object, required: true },
    index: { type: Number, required: true },
});

const expanded = ref(props.entry.status === 'pending');

const meta = computed(() => stateMeta(props.entry.status));
const title = computed(() => {
    const e = props.entry;
    if (e.habit?.name) return e.habit.name;
    if (e.habit_id) {
        const habit = props.editor.state.habits.find((h) => h.habit_id === e.habit_id);
        if (habit?.name) return habit.name;
    }
    return e.custom_activity || 'Actividad sin nombre';
});
const duration = computed(() => entryDurationLabel(props.entry));
const timeRange = computed(() =>
    props.entry.start_time && props.entry.end_time ? `${props.entry.start_time} → ${props.entry.end_time}` : 'Sin horario'
);

const habitSelectValue = computed(() => {
    if (props.entry.habit_id) return String(props.entry.habit_id);
    if (props.entry.habit_occurrence_id && props.entry.habit) return String(props.entry.habit.habit_id);
    if (!props.entry.habit_id && !props.entry.habit_occurrence_id) return 'free';
    return '';
});

const inputClass =
    'w-full rounded-lg border border-line-200 bg-card px-3 py-2 text-[13px] text-ink-900 focus:border-brand-700 focus:outline-none focus:ring-1 focus:ring-brand-700';

function update(field, value) {
    props.editor.updateEntry(props.index, { [field]: value });
}

function onHabitChange(event) {
    const val = event.target.value;
    if (val === 'free') {
        props.editor.updateEntry(props.index, { habit_id: null, habit_occurrence_id: null, custom_activity: '', habit: null });
    } else if (val) {
        const habit = props.editor.state.habits.find((h) => h.habit_id === Number(val));
        props.editor.updateEntry(props.index, { habit_id: Number(val), habit_occurrence_id: null, custom_activity: null, habit: habit ?? null });
    }
}

const errorFor = (field) => props.editor.errorFor(`entries.${props.index}.${field}`);
</script>

<template>
    <article class="overflow-hidden rounded-xl border border-line-200 bg-card">
        <!-- Collapsed header -->
        <button type="button" class="flex w-full items-center gap-3 px-4 py-3 text-left" @click="expanded = !expanded">
            <span class="h-12 w-1.5 shrink-0 rounded-full" :style="{ background: meta.rail }"></span>
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    <h3 class="truncate font-display text-[19px] text-ink-900">{{ title }}</h3>
                    <ReportChip v-if="entry.is_free_activity" variant="neutral" small>Extra</ReportChip>
                </div>
                <p class="font-mono text-[11px] text-ink-500">
                    {{ timeRange }}<span v-if="duration" class="text-ink-400"> · {{ duration }}</span>
                </p>
            </div>
            <ReportChip :variant="meta.variant" dot>{{ meta.label }}</ReportChip>
            <svg class="h-4 w-4 shrink-0 text-ink-400 transition" :class="expanded ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
            </svg>
        </button>

        <!-- Expanded body -->
        <div v-if="expanded" class="space-y-4 border-t border-line-100 bg-paper p-5">
            <div>
                <label class="mb-1 block font-mono text-[10px] uppercase text-ink-400">Actividad</label>
                <select :value="habitSelectValue" :class="inputClass" @change="onHabitChange">
                    <option value="" disabled>Seleccionar actividad…</option>
                    <optgroup label="Mis hábitos">
                        <option v-for="habit in editor.state.habits" :key="habit.habit_id" :value="String(habit.habit_id)">{{ habit.name }}</option>
                    </optgroup>
                    <option value="free">Otra actividad</option>
                </select>
                <p v-if="errorFor('habit_id')" class="mt-1 text-[11px] text-danger-2">{{ errorFor('habit_id') }}</p>
            </div>

            <div v-if="habitSelectValue === 'free'">
                <label class="mb-1 block font-mono text-[10px] uppercase text-ink-400">Nombre</label>
                <input type="text" :value="entry.custom_activity" :class="inputClass" placeholder="Nombre de la actividad…" @input="update('custom_activity', $event.target.value)" />
                <p v-if="errorFor('custom_activity')" class="mt-1 text-[11px] text-danger-2">{{ errorFor('custom_activity') }}</p>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block font-mono text-[10px] uppercase text-ink-400">Inicio</label>
                    <input type="time" :value="entry.start_time" :class="[inputClass, 'font-mono']" @input="update('start_time', $event.target.value)" />
                    <p v-if="errorFor('start_time')" class="mt-1 text-[11px] text-danger-2">{{ errorFor('start_time') }}</p>
                </div>
                <div>
                    <label class="mb-1 block font-mono text-[10px] uppercase text-ink-400">Fin</label>
                    <input type="time" :value="entry.end_time" :class="[inputClass, 'font-mono']" @input="update('end_time', $event.target.value)" />
                    <p v-if="errorFor('end_time')" class="mt-1 text-[11px] text-danger-2">{{ errorFor('end_time') }}</p>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block font-mono text-[10px] uppercase text-ink-400">Estado</label>
                <StateSelector :selected="entry.status" @select="(s) => update('status', s)" />
                <p v-if="errorFor('status')" class="mt-1 text-[11px] text-danger-2">{{ errorFor('status') }}</p>
            </div>

            <div>
                <label class="mb-1 block font-mono text-[10px] uppercase text-ink-400">Notas</label>
                <textarea :value="entry.notes" :class="[inputClass, 'resize-none']" rows="2" placeholder="Notas (opcional)…" @input="update('notes', $event.target.value)"></textarea>
            </div>

            <div class="flex items-center justify-between">
                <button type="button" class="text-[12px] text-danger-2 hover:underline" @click="editor.removeEntry(index)">Quitar del reporte</button>
            </div>
        </div>
    </article>
</template>
