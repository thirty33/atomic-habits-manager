import { BaseHttpClient } from "./BaseHttpClient";
import type { HabitGateway, ActionRequest, MutationResult } from "../../application/ports";
import type { FormValues } from "../../domain/node-schema";

export class HabitHttpService extends BaseHttpClient implements HabitGateway {
    update(action: ActionRequest, payload: FormValues): Promise<MutationResult> {
        return this.send(action, payload);
    }
}