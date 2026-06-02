import { reactive, ref } from "vue";
import type { FieldDefinition, FormValues, ModalModel } from "../domain/node-schema";
import type { ActionRequest, HabitGateway, MutationResult } from "../application/ports";
import { RequestError, ValidationError } from "../application/errors";
import { initialValues } from "../domain/form";

export interface SubmitCallbacks {
    onSuccess: (result: MutationResult) => void;
    onError: (message: string) => void;
}

export interface NodeFormController {
    form: FormValues;
    errorFor: (name: string) => string | null;
    cssClassFor: (name: string) => string;
    getPayload: () => FormValues;
    submit: (action: ActionRequest, gateway: HabitGateway, callbacks: SubmitCallbacks) => Promise<void>;
}

/** Vue adapter: reactive form values + submit. Seeding/visibility live in domain/form. */
export function useNodeForm(
    fields: FieldDefinition[],
    { model = null }: { model?: ModalModel } = {},
): NodeFormController {
    const form = reactive<FormValues>(initialValues(fields, model));
    const errors = reactive<Record<string, string[]>>({});
    const submitted = ref(false);

    const errorFor = (name: string): string | null => errors[name]?.[0] ?? null;

    // Mirrors useForm.js cssClassFor (the *_id vs default border split).
    const cssClassFor = (name: string): string => {
        if (!submitted.value || !errorFor(name)) {
            return "";
        }
        return name.endsWith("_id")
            ? "border-red-500 dark:border-red-500"
            : "border-red-500 bg-red-100 dark:border-red-500 dark:bg-red-500";
    };

    const submit = async (
        action: ActionRequest,
        gateway: HabitGateway,
        callbacks: SubmitCallbacks,
    ): Promise<void> => {
        clear(errors);
        try {
            callbacks.onSuccess(await gateway.update(action, { ...form }));
        } catch (error) {
            if (error instanceof ValidationError) {
                Object.assign(errors, error.errors);
            } else if (error instanceof RequestError) {
                callbacks.onError(error.message);
            }
        } finally {
            submitted.value = true;
        }
    };

    return { form, errorFor, cssClassFor, getPayload: () => ({ ...form }), submit };
}

function clear(target: Record<string, unknown>): void {
    for (const key of Object.keys(target)) {
        delete target[key];
    }
}