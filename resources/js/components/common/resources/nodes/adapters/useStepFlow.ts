import { ref, computed, type Ref, type ComputedRef } from "vue";
import type { NodeStep } from "../domain/node-schema";
import { FIRST_INDEX, stepAt, isFirst, isLast, nextIndex, prevIndex } from "../domain/wizard";

export interface StepFlowController {
    currentIndex: Ref<number>;
    currentStep: ComputedRef<NodeStep>;
    isFirst: ComputedRef<boolean>;
    isLast: ComputedRef<boolean>;
    goNext: () => void;
    goBack: () => void;
}

/** Vue adapter: binds the pure wizard transitions (domain/wizard) to reactive state. */
export function useStepFlow(steps: NodeStep[]): StepFlowController {
    const currentIndex = ref(FIRST_INDEX);

    return {
        currentIndex,
        currentStep: computed(() => stepAt(steps, currentIndex.value)),
        isFirst: computed(() => isFirst(currentIndex.value)),
        isLast: computed(() => isLast(currentIndex.value, steps.length)),
        goNext: () => {
            currentIndex.value = nextIndex(currentIndex.value, steps.length);
        },
        goBack: () => {
            currentIndex.value = prevIndex(currentIndex.value);
        },
    };
}
