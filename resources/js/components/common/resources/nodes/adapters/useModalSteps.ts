import { computed, reactive, ref } from "vue";
import { NodeKind, type ActionRequest, type ModalModel, type NodeModalData, type NodeStep } from "../domain/node-schema";
import type { HabitGateway, HabitScheduleGateway } from "../application/ports";
import type { Notifier } from "../application/notifier";
import { capturedScheduleAction, resolveFormAction, resolveScheduleAction } from "../application/step-actions";
import { useStepFlow } from "./useStepFlow";
import { useNodeForm, type NodeFormController } from "./useNodeForm";
import { useListNode, type ListNodeController } from "./useListNode";

type StepState =
    | { kind: typeof NodeKind.List; list: ListNodeController }
    | { kind: typeof NodeKind.Form; form: NodeFormController };

/** Collaborators the controller orchestrates — injected, never imported concretely here. */
export interface ModalStepsDeps {
    habitGateway: HabitGateway;
    scheduleGateway: HabitScheduleGateway;
    notifier: Notifier;
}

/** Lifecycle the host component owns (Vue emits stay in the component). */
export interface ModalStepsLifecycle {
    /** Un submit cambió datos en el servidor (la tabla quedó desactualizada). */
    onMutated: () => void;
    /** El flujo terminó: cerrar el modal (y refrescar). */
    onCompleted: () => void;
}

/** Unwrapped reactive contract — the whole controller is passed around and auto-unwraps in templates. */
export interface ModalStepsController {
    steps: NodeStep[];
    currentIndex: number;
    currentStep: NodeStep;
    isFirst: boolean;
    isLast: boolean;
    busy: boolean;
    nodeBindings: Record<string, unknown>;
    goBack: () => void;
    submitCurrentStep: () => Promise<void>;
    skipCurrentStep: () => void;
}

/**
 * Application controller for the node-based step modal (Two Step View). Owns the
 * per-step state, the wizard flow and the submit orchestration so the host
 * component only wires dependencies and renders. Form steps persist the habit;
 * list steps sync its schedules. Side-effects go through the Notifier port;
 * navigation/close is signalled via the lifecycle hooks.
 */
export function useModalSteps(
    modalData: NodeModalData,
    model: ModalModel,
    deps: ModalStepsDeps,
    lifecycle: ModalStepsLifecycle,
): ModalStepsController {
    const steps = modalData.steps;

    // One state holder per step, created once (steps are static for the modal's life).
    const stepStates: StepState[] = steps.map((step): StepState => {
        if (step.content.kind === NodeKind.List) {
            return { kind: NodeKind.List, list: useListNode(step.content, { model }) };
        }

        return { kind: NodeKind.Form, form: useNodeForm(step.content.fields, { model }) };
    });

    const { currentIndex, currentStep, isFirst, isLast, goNext, goBack } = useStepFlow(steps);

    // Cross-step capture: the sync action of the habit created in step 1 (create flow).
    const createdScheduleAction = ref<ActionRequest | null>(null);
    const busy = ref(false);

    const nodeBindings = computed<Record<string, unknown>>(() => {
        const state = stepStates[currentIndex.value];
        if (state.kind === NodeKind.List) {
            return { list: state.list };
        }

        return { form: state.form.form, errorFor: state.form.errorFor, cssClassFor: state.form.cssClassFor };
    });

    const advanceOrComplete = (): void => {
        if (isLast.value) {
            lifecycle.onCompleted();
        } else {
            goNext();
        }
    };

    const submitHabit = async (form: NodeFormController): Promise<void> => {
        const action = resolveFormAction(model, currentStep.value);
        if (!action?.url) {
            deps.notifier.error("No hay acción de guardado para el hábito.");

            return;
        }

        await form.submit(action, deps.habitGateway, {
            onSuccess: (result) => {
                lifecycle.onMutated();
                deps.notifier.success(result);
                createdScheduleAction.value = capturedScheduleAction(result) ?? createdScheduleAction.value;
                advanceOrComplete();
            },
            onError: (message) => deps.notifier.error(message),
        });
    };

    const syncSchedules = async (list: ListNodeController): Promise<void> => {
        const action = resolveScheduleAction(model, createdScheduleAction.value);

        await list.submitAll(
            deps.scheduleGateway,
            {
                onSuccess: (result) => {
                    lifecycle.onMutated();
                    deps.notifier.success(result);
                    lifecycle.onCompleted();
                },
                onError: (message) => deps.notifier.error(message),
            },
            action ?? undefined,
        );
    };

    const submitCurrentStep = async (): Promise<void> => {
        if (busy.value) {
            return;
        }

        const state = stepStates[currentIndex.value];
        busy.value = true;
        try {
            if (state.kind === NodeKind.Form) {
                await submitHabit(state.form);
            } else {
                await syncSchedules(state.list);
            }
        } finally {
            busy.value = false;
        }
    };

    // Optional step (create flow): the habit already exists from step 1; just close.
    const skipCurrentStep = (): void => {
        lifecycle.onCompleted();
    };

    return reactive({
        steps,
        currentIndex,
        currentStep,
        isFirst,
        isLast,
        busy,
        nodeBindings,
        goBack,
        submitCurrentStep,
        skipCurrentStep,
    }) as ModalStepsController;
}
