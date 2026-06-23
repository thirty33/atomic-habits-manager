<script setup>
import MoodSelector from './MoodSelector.vue';

const props = defineProps({
    editor: { type: Object, required: true },
});
</script>

<template>
    <section class="rounded-xl border border-line-200 bg-card p-5">
        <p class="font-mono text-[11px] uppercase text-ink-400">Reflexión del día</p>
        <h2 class="mb-3 font-display text-[20px] text-ink-900">¿Cómo te sentiste?</h2>

        <MoodSelector
            :moods="editor.state.moods"
            :selected="editor.state.report?.mood"
            @select="(m) => editor.updateReportField('mood', m)"
        />

        <div class="mt-4">
            <label class="mb-1 block font-mono text-[10px] uppercase text-ink-400">Notas</label>
            <textarea
                :value="editor.state.report?.notes ?? ''"
                rows="3"
                placeholder="¿Algo que quieras anotar sobre este día?"
                class="w-full resize-none rounded-lg border border-line-200 bg-card px-3 py-2 text-[13px] text-ink-900 focus:border-brand-700 focus:outline-none focus:ring-1 focus:ring-brand-700"
                @input="editor.updateReportField('notes', $event.target.value)"
            ></textarea>
        </div>
    </section>
</template>
