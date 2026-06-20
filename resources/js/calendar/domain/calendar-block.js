// Calendar domain — the presentation block + pure time-grid layout functions.

import { toDecimalHour } from './calendar-date.js';

/** A single occurrence rendered on the calendar (value-equal by id). */
export class CalendarBlock {
    constructor(dto) {
        this.id = dto.habit_occurrence_id;
        this.habitId = dto.habit_id;
        this.name = dto.habit?.name ?? 'Hábito';
        this.accent = dto.habit?.accent ?? 'brand';
        this.color = dto.habit?.color ?? null;
        this.status = dto.status ?? 'pending';
        this.date = String(dto.occurrence_date).slice(0, 10);
        this.endDate = String(dto.end_date ?? dto.occurrence_date).slice(0, 10);
        this.startTime = String(dto.start_time ?? '00:00').slice(0, 5);
        this.endTime = String(dto.end_time ?? '00:00').slice(0, 5);
        this.start = toDecimalHour(this.startTime);
        this.end = toDecimalHour(this.endTime);
        this.sub = dto.habit?.habit_nature_label ?? null;
        this.natureLabel = dto.habit?.habit_nature_label ?? null;
        this.desireLabel = dto.habit?.desire_type_label ?? null;
        Object.freeze(this);
    }

    get overnight() {
        return this.end <= this.start || this.endDate !== this.date;
    }

    get rangeLabel() {
        return this.overnight
            ? `${this.startTime} → ${this.endTime}`
            : `${this.startTime}–${this.endTime}`;
    }
}

const segmentOf = (block, extra) => ({
    id: block.id,
    name: block.name,
    accent: block.accent,
    status: block.status,
    sub: block.sub,
    startTime: block.startTime,
    endTime: block.endTime,
    realStart: block.start,
    realEnd: block.end,
    overnight: block.overnight,
    slot: 0,
    slots: 1,
    ...extra,
});

/**
 * Split every block into day-bound segments. Overnight blocks (end <= start)
 * become a "head" piece (start -> 24:00 on day N) and a "tail" piece
 * (00:00 -> end on day N+1).
 */
export function expandSegments(blocks) {
    const segments = [];
    for (const block of blocks) {
        if (block.overnight) {
            segments.push(segmentOf(block, { dayISO: block.date, start: block.start, end: 24, part: 'head' }));
            segments.push(segmentOf(block, { dayISO: block.endDate, start: 0, end: block.end, part: 'tail' }));
        } else {
            segments.push(segmentOf(block, { dayISO: block.date, start: block.start, end: block.end, part: 'full' }));
        }
    }
    return segments;
}

/** Greedy side-by-side packing of overlapping segments → assigns {slot, slots}. */
export function packOverlaps(segments) {
    const items = [...segments].sort((a, b) => a.start - b.start || a.end - b.end);
    const result = [];
    let cluster = [];
    let clusterEnd = -1;

    const flush = () => {
        const colEnds = [];
        for (const item of cluster) {
            let placed = false;
            for (let i = 0; i < colEnds.length; i++) {
                if (item.start >= colEnds[i]) {
                    item.slot = i;
                    colEnds[i] = item.end;
                    placed = true;
                    break;
                }
            }
            if (!placed) {
                item.slot = colEnds.length;
                colEnds.push(item.end);
            }
        }
        for (const item of cluster) {
            item.slots = colEnds.length;
            result.push(item);
        }
        cluster = [];
    };

    for (const item of items) {
        if (cluster.length && item.start >= clusterEnd) {
            flush();
        }
        cluster.push(item);
        clusterEnd = cluster.length === 1 ? item.end : Math.max(clusterEnd, item.end);
    }
    if (cluster.length) {
        flush();
    }
    return result;
}

/** Packed segments that fall on a given day (ISO string). */
export function layoutDay(blocks, dayISO) {
    return packOverlaps(expandSegments(blocks).filter((segment) => segment.dayISO === dayISO));
}

/** Blocks anchored on a given day (by occurrence_date). */
export function blocksOnDay(blocks, dayISO) {
    return blocks
        .filter((block) => block.date === dayISO)
        .sort((a, b) => a.start - b.start || a.end - b.end);
}

/** Is this segment happening right now? */
export function segmentIsNow(segment, nowDecimal, todayISO) {
    return segment.dayISO === todayISO && segment.start <= nowDecimal && nowDecimal < segment.end;
}
