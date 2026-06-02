<script lang="ts">
export default { name: "AppModalSteps" };
</script>

<script setup lang="ts">
import type { NodeModalData, ModalModel } from "../domain/node-schema";
import { habitGateway, habitScheduleGateway } from "../infrastructure/http/container";
import { useToastNotifier } from "../adapters/useToastNotifier";
import { useModalSteps } from "../adapters/useModalSteps";
import AppNode from "./AppNode.vue";

const props = defineProps<{
    modalData: NodeModalData; // { schema:'node', steps:[...] }
    model?: ModalModel;        // habit row in edit; absent in create
}>();

const emit = defineEmits<{ processed: [data?: unknown]; close: []; mutated: [] }>();

// Application controller: owns step state, flow and submit orchestration. This
// component only wires dependencies (gateways + notifier) and lifecycle (emits).
const wizard = useModalSteps(
    props.modalData,
    props.model ?? null,
    {
        habitGateway,
        scheduleGateway: habitScheduleGateway,
        notifier: useToastNotifier(),
    },
    {
        onMutated: () => emit("mutated"),
        onCompleted: () => {
            emit("processed");
            emit("close");
        },
    },
);
</script>

<template>
    <div class="p-5 pt-10 font-body text-ink-900">
        <!-- Stepper -->
        <div class="flex items-center justify-between gap-4 mb-5 pb-3 border-b border-line-200">
            <div class="flex items-center gap-2.5">
                <template v-for="(step, index) in wizard.steps" :key="step.step">
                    <span
                        class="grid place-items-center w-[26px] h-[26px] rounded-full font-mono text-[12px] transition-shadow"
                        :class="index < wizard.currentIndex
                            ? 'bg-brand-700 text-paper'
                            : index === wizard.currentIndex
                                ? 'bg-brand-700 text-paper ring-4 ring-brand-100'
                                : 'bg-card text-ink-400 ring-1 ring-inset ring-line-300'"
                    >
                        <svg
                            v-if="index < wizard.currentIndex"
                            width="12" height="12" viewBox="0 0 16 16" fill="none" aria-hidden="true"
                        >
                            <path d="M3 8l3.5 3.5L13 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <template v-else>{{ step.step }}</template>
                    </span>
                    <span v-if="index < wizard.steps.length - 1" class="w-7 h-px bg-line-200"></span>
                </template>
            </div>
            <span class="font-mono text-[11px] tracking-[0.12em] uppercase text-ink-400 whitespace-nowrap">
                Paso {{ wizard.currentIndex + 1 }} de {{ wizard.steps.length }}
            </span>
        </div>

        <!-- Title -->
        <h2 class="font-display text-[26px] lg:text-[28px] leading-[1.05] text-ink-900">{{ wizard.currentStep.title }}</h2>
        <p v-if="wizard.currentStep.subtitle" class="text-[13px] leading-relaxed text-ink-500 mt-1.5 mb-5 max-w-[520px]">
            {{ wizard.currentStep.subtitle }}
        </p>
        <div v-else class="mb-5"></div>

        <!-- Body -->
        <AppNode :node="wizard.currentStep.content" v-bind="wizard.nodeBindings" />

        <!-- Footer -->
        <div class="flex justify-between items-center gap-3 mt-7 -mx-5 -mb-5 px-5 py-4 bg-paper border-t border-line-200 rounded-b-2xl">
            <button
                v-if="!wizard.isFirst"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-[13.5px] font-medium text-ink-700 hover:bg-line-100 disabled:opacity-50 transition-colors"
                :disabled="wizard.busy"
                @click="wizard.goBack"
            >← Atrás</button>
            <div v-else></div>

            <div class="flex items-center gap-2.5">
                <button
                    v-if="wizard.currentStep.is_optional"
                    type="button"
                    class="px-3.5 py-2 rounded-lg text-[13.5px] font-medium text-ink-700 ring-1 ring-inset ring-line-300 hover:bg-line-100 disabled:opacity-50 transition-colors"
                    :disabled="wizard.busy"
                    @click="wizard.skipCurrentStep"
                >{{ wizard.currentStep.skip_text || "Omitir" }}</button>

                <button
                    type="button"
                    class="inline-flex items-center gap-2 px-[18px] py-[11px] rounded-lg bg-brand-700 text-paper font-medium text-[13.5px] leading-none hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-700/30 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    :disabled="wizard.busy"
                    @click="wizard.submitCurrentStep"
                >{{ wizard.currentStep.submit_text }}</button>
            </div>
        </div>
    </div>
</template>
