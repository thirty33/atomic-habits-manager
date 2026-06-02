import { BaseHttpClient } from "./BaseHttpClient";
import type { HabitScheduleGateway, ActionRequest, MutationResult, SchedulesPayload } from "../../application/ports";

export class HabitScheduleHttpService extends BaseHttpClient implements HabitScheduleGateway {
    sync(action: ActionRequest, payload: SchedulesPayload): Promise<MutationResult> {
        return this.send(action, payload);
    }
}