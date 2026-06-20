// Calendar domain — date & time value helpers (pure, no framework).

const pad = (n) => String(n).padStart(2, '0');

/**
 * Immutable calendar date (no time component, local midnight).
 */
export class CalendarDate {
    constructor(year, month, day) {
        this._d = new Date(year, month - 1, day);
        Object.freeze(this);
    }

    static parse(iso) {
        const [y, m, d] = String(iso).slice(0, 10).split('-').map(Number);
        return new CalendarDate(y, m, d);
    }

    static today() {
        const n = new Date();
        return new CalendarDate(n.getFullYear(), n.getMonth() + 1, n.getDate());
    }

    get year() { return this._d.getFullYear(); }
    get month() { return this._d.getMonth() + 1; }
    get day() { return this._d.getDate(); }
    get weekday() { return this._d.getDay(); } // 0 = Sunday

    toISO() { return `${this.year}-${pad(this.month)}-${pad(this.day)}`; }
    toString() { return this.toISO(); }
    time() { return this._d.getTime(); }

    addDays(n) {
        const x = new Date(this._d);
        x.setDate(x.getDate() + n);
        return new CalendarDate(x.getFullYear(), x.getMonth() + 1, x.getDate());
    }

    addMonths(n) {
        const x = new Date(this.year, this.month - 1 + n, 1);
        const daysInTarget = new Date(x.getFullYear(), x.getMonth() + 1, 0).getDate();
        return new CalendarDate(x.getFullYear(), x.getMonth() + 1, Math.min(this.day, daysInTarget));
    }

    startOfWeek(weekStartsOn = 0) {
        const diff = (this.weekday - weekStartsOn + 7) % 7;
        return this.addDays(-diff);
    }

    startOfMonth() { return new CalendarDate(this.year, this.month, 1); }
    daysInMonth() { return new Date(this.year, this.month, 0).getDate(); }

    isSameDay(other) { return this.toISO() === other.toISO(); }
    isBefore(other) { return this.time() < other.time(); }
    isAfter(other) { return this.time() > other.time(); }
    isToday() { return this.isSameDay(CalendarDate.today()); }
    isSameMonth(other) { return this.year === other.year && this.month === other.month; }
}

/** 'HH:MM' -> decimal hours (e.g. '07:30' -> 7.5). */
export const toDecimalHour = (hhmm) => {
    const [h, m] = String(hhmm).split(':').map(Number);
    return h + (m || 0) / 60;
};

/** decimal hours -> 'HH:MM'. */
export const fmtHour = (decimal) => {
    const total = Math.round(decimal * 60);
    return `${pad(Math.floor(total / 60) % 24)}:${pad(total % 60)}`;
};

/** Current local time as decimal hours. */
export const nowDecimalHour = () => {
    const n = new Date();
    return n.getHours() + n.getMinutes() / 60;
};

const cap = (s) => s.charAt(0).toUpperCase() + s.slice(1);

export const weekdayShort = (date) =>
    new Intl.DateTimeFormat('es-ES', { weekday: 'short' }).format(date._d).replace('.', '');

export const weekdayLong = (date) =>
    cap(new Intl.DateTimeFormat('es-ES', { weekday: 'long' }).format(date._d));

export const monthLong = (date) =>
    new Intl.DateTimeFormat('es-ES', { month: 'long' }).format(date._d);

export const monthShort = (date) =>
    new Intl.DateTimeFormat('es-ES', { month: 'short' }).format(date._d).replace('.', '');
