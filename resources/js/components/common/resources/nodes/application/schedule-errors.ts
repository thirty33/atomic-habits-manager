export interface ItemErrors {
    index: number;
    errors: Record<string, string[]>;
}

/**
 * Splits a Laravel 422 bag keyed `schedules.{i}.{field}` into per-item field maps,
 * so each accordion item can show its own errors. Keys without that shape are ignored.
 */
export function mapScheduleErrors(bag: Record<string, string[]>): ItemErrors[] {
    const byIndex = new Map<number, Record<string, string[]>>();

    for (const [key, messages] of Object.entries(bag)) {
        const match = key.match(/^schedules\.(\d+)\.(.+)$/);
        if (!match) {
            continue;
        }
        const index = Number(match[1]);
        const field = match[2];
        const entry = byIndex.get(index) ?? {};
        entry[field] = messages;
        byIndex.set(index, entry);
    }

    return [...byIndex.entries()]
        .map(([index, errors]) => ({ index, errors }))
        .sort((a, b) => a.index - b.index);
}