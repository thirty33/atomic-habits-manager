import type { MutationResult } from "./ports";

/**
 * Outbound port for user-facing notifications. Keeps the step-submission
 * orchestration (adapters/useModalSteps) free of any concrete toast or DOM
 * concern — hexagonal style, side-effects live behind a port. The Vue
 * implementation is adapters/useToastNotifier.
 */
export interface Notifier {
    success(result: MutationResult): void;
    error(message: string): void;
}
