import type { FormValues, ActionRequest } from "../domain/node-schema";

/** Re-export so existing imports keep resolving ActionRequest from the ports module. */
export type { ActionRequest };

/** The controllers' success toast payload. */
export interface MutationResult {
    title?: string;
    message?: string;
    timeout?: number;
    [key: string]: unknown;
}

/** Full desired set of schedules sent to the sync endpoint. */
export interface SchedulesPayload {
    schedules: FormValues[];
}

/** Port: persists a single habit. */
export interface HabitGateway {
    update(action: ActionRequest, payload: FormValues): Promise<MutationResult>;
}

/** Port: syncs the whole set of schedules of a habit (create/update/delete in one transaction). */
export interface HabitScheduleGateway {
    sync(action: ActionRequest, payload: SchedulesPayload): Promise<MutationResult>;
}