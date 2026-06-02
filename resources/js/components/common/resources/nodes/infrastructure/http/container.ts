import { HabitHttpService } from "./HabitHttpService";
import { HabitScheduleHttpService } from "./HabitScheduleHttpService";
import type { HabitGateway, HabitScheduleGateway } from "../../application/ports";

/** Default production singletons (tests pass fakes that satisfy the same ports). */
export const habitGateway: HabitGateway = new HabitHttpService();
export const habitScheduleGateway: HabitScheduleGateway = new HabitScheduleHttpService();