import type { ActionRequest, ModalModel, NodeStep } from "../domain/node-schema";
import type { MutationResult } from "./ports";

/**
 * Resolves the habit form action: in edit the model carries update_action (PUT);
 * in create the step bakes its own store action (POST).
 */
export function resolveFormAction(model: ModalModel, step: NodeStep): ActionRequest | null {
    return (model?.update_action as ActionRequest | undefined) ?? step.action ?? null;
}

/**
 * Resolves the schedules sync action: in edit the model carries it; in create it
 * is captured from step 1's response (see capturedScheduleAction).
 */
export function resolveScheduleAction(model: ModalModel, captured: ActionRequest | null): ActionRequest | null {
    return (model?.schedules_sync_action as ActionRequest | undefined) ?? captured ?? null;
}

/**
 * Pulls the freshly-created habit's sync action out of a create response, so the
 * create flow can chain step 1 (POST habit) into step 2 (PUT schedules).
 */
export function capturedScheduleAction(result: MutationResult): ActionRequest | null {
    const habit = (result.extra as { habit?: { schedules_sync_action?: ActionRequest } } | undefined)?.habit;

    return habit?.schedules_sync_action ?? null;
}
