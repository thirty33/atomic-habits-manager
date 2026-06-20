// Calendar domain — view ranges, navigation and grid day lists (pure).

import { monthLong, monthShort } from './calendar-date.js';

const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1);

/** The date range a view needs to fetch for a given anchor. */
export function rangeFor(view, anchor) {
    let from;
    let to;
    if (view === 'mes') {
        from = anchor.startOfMonth().startOfWeek(0);
        to = from.addDays(41); // 6 weeks grid
    } else if (view === 'dia') {
        from = anchor;
        to = anchor;
    } else {
        from = anchor.startOfWeek(0); // semana | lista
        to = from.addDays(6);
    }
    // Fetch one extra day before the window so an overnight block anchored on
    // the previous day renders its "viene de anoche" tail on the first visible
    // day (the tail piece lives on occurrence_date + 1).
    return { from: from.addDays(-1), to };
}

/** Move the anchor one step backward/forward for the active view. */
export function stepAnchor(view, anchor, direction) {
    if (view === 'mes') {
        return anchor.addMonths(direction);
    }
    if (view === 'dia') {
        return anchor.addDays(direction);
    }
    return anchor.addDays(7 * direction);
}

/** The 7 days of the anchor's week (Sunday-first). */
export function weekDays(anchor) {
    const start = anchor.startOfWeek(0);
    return Array.from({ length: 7 }, (_, i) => start.addDays(i));
}

/** The 42 days (6×7) of the anchor's month grid. */
export function monthGridDays(anchor) {
    const start = anchor.startOfMonth().startOfWeek(0);
    return Array.from({ length: 42 }, (_, i) => start.addDays(i));
}

/** Human title for the toolbar. */
export function rangeLabel(view, anchor) {
    if (view === 'dia') {
        return `${anchor.day} de ${monthLong(anchor)} ${anchor.year}`;
    }
    if (view === 'mes') {
        return `${cap(monthLong(anchor))} ${anchor.year}`;
    }
    const days = weekDays(anchor);
    const [a, b] = [days[0], days[6]];
    if (a.month === b.month) {
        return `${a.day} – ${b.day} ${monthShort(a)} ${a.year}`;
    }
    return `${a.day} ${monthShort(a)} – ${b.day} ${monthShort(b)} ${b.year}`;
}
