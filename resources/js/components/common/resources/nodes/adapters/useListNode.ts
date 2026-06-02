import { reactive, ref, computed } from "vue";
import type { ListNode, FormValues, ModalModel } from "../domain/node-schema";
import type { ActionRequest, HabitScheduleGateway, MutationResult } from "../application/ports";
import { RequestError, ValidationError } from "../application/errors";
import { seedItems, blankItem, canAdd, canRemove } from "../domain/list";
import { summarize } from "../domain/form";
import { mapScheduleErrors } from "../application/schedule-errors";

interface ListItem {
    key: number;
    form: FormValues;
    identity: unknown; // habit_schedule_id for existing items; null for new ones
    errors: Record<string, string[]>;
}

export interface SubmitCallbacks {
    onSuccess: (result: MutationResult) => void;
    onError: (message: string) => void;
}

/**
 * Unwrapped reactive contract: useListNode returns reactive({...}) so the WHOLE
 * controller can be passed as a prop and its members auto-unwrap in the template.
 */
export interface ListNodeController {
    items: ListItem[];
    expandedIndex: number;
    canAdd: boolean;
    canRemove: boolean;
    addItem: () => void;
    removeItem: (index: number) => void;
    toggle: (index: number) => void;
    summaryFor: (index: number) => string;
    itemErrorFor: (index: number) => (name: string) => string | null;
    itemCssClassFor: (index: number) => (name: string) => string;
    submitAll: (
        gateway: HabitScheduleGateway,
        callbacks: SubmitCallbacks,
        actionOverride?: ActionRequest,
    ) => Promise<void>;
}

let keySeq = 0;

export function useListNode(
    node: ListNode,
    { model = null }: { model?: ModalModel } = {},
): ListNodeController {
    const syncAction = (node.sync_action_key ? (model?.[node.sync_action_key] as ActionRequest) : null) ?? null;

    const items = reactive<ListItem[]>(
        seedItems(node, model).map((seed) => ({
            key: keySeq++,
            form: seed.values,
            identity: seed.identity,
            errors: {},
        })),
    );
    const expandedIndex = ref(0);

    const addItem = (): void => {
        if (!canAdd(node, items.length)) {
            return;
        }
        items.push({ key: keySeq++, form: blankItem(node), identity: null, errors: {} });
        expandedIndex.value = items.length - 1;
    };

    const removeItem = (index: number): void => {
        if (!canRemove(node, items.length)) {
            return;
        }
        items.splice(index, 1);
        if (expandedIndex.value >= items.length) {
            expandedIndex.value = items.length - 1;
        }
    };

    const toggle = (index: number): void => {
        expandedIndex.value = expandedIndex.value === index ? -1 : index;
    };

    const itemErrorFor = (index: number) => (name: string): string | null =>
        items[index]?.errors[name]?.[0] ?? null;

    const itemCssClassFor = (index: number) => (name: string): string =>
        items[index]?.errors[name]
            ? "border-red-500 bg-red-100 dark:border-red-500 dark:bg-red-500"
            : "";

    const submitAll = async (
        gateway: HabitScheduleGateway,
        callbacks: SubmitCallbacks,
        actionOverride?: ActionRequest,
    ): Promise<void> => {
        items.forEach((item) => clear(item.errors));

        const action = actionOverride ?? syncAction;
        if (action === null) {
            callbacks.onError("No hay acción de guardado para las programaciones.");
            return;
        }

        const schedules = items.map((item) => ({
            ...item.form,
            ...(item.identity != null ? { habit_schedule_id: item.identity } : {}),
        }));

        try {
            callbacks.onSuccess(await gateway.sync(action, { schedules }));
        } catch (error) {
            if (error instanceof ValidationError) {
                const failures = mapScheduleErrors(error.errors);
                failures.forEach((failure) => {
                    if (items[failure.index]) {
                        Object.assign(items[failure.index].errors, failure.errors);
                    }
                });
                if (failures.length > 0) {
                    expandedIndex.value = failures[0].index;
                }
                callbacks.onError("Revisa los campos marcados en las programaciones.");
            } else if (error instanceof RequestError) {
                callbacks.onError(error.message);
            }
        }
    };

    return reactive({
        items,
        expandedIndex,
        canAdd: computed(() => canAdd(node, items.length)),
        canRemove: computed(() => canRemove(node, items.length)),
        addItem,
        removeItem,
        toggle,
        summaryFor: (index: number) => summarize(items[index].form, node.summary_fields ?? []),
        itemErrorFor,
        itemCssClassFor,
        submitAll,
    }) as ListNodeController;
}

function clear(target: Record<string, unknown>): void {
    for (const key of Object.keys(target)) {
        delete target[key];
    }
}