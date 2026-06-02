import { inject } from "vue";
import type { Notifier } from "../application/notifier";

type ToastFn = (title?: string, message?: string, timeout?: number) => void;

/**
 * Presentation adapter implementing the Notifier port over the global toast
 * functions exposed via Vue inject. The orchestration layer depends on the
 * Notifier port, never on these inject keys or the toast signature. Must be
 * called during component setup (inject).
 */
export function useToastNotifier(): Notifier {
    const addSuccessToast = inject<ToastFn>("addSuccessToast", () => {});
    const addErrorToast = inject<ToastFn>("addErrorToast", () => {});

    return {
        success: (result) => addSuccessToast(result.title, result.message, result.timeout),
        error: (message) => addErrorToast("Error", message),
    };
}
