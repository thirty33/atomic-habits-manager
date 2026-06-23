// Reports domain — entry status & chip-variant semantics (REAL enum, redesign tokens).

// Soft chip palette: tinted background + colored foreground, using the live tokens.
export const CHIP = {
    success: { bg: 'rgb(var(--color-success-2) / 0.12)', fg: 'rgb(var(--color-success-2))' },
    warning: { bg: 'rgb(var(--color-warning) / 0.14)', fg: 'rgb(var(--color-warning))' },
    danger: { bg: 'rgb(var(--color-danger-2) / 0.12)', fg: 'rgb(var(--color-danger-2))' },
    info: { bg: 'rgb(var(--color-info-2) / 0.12)', fg: 'rgb(var(--color-info-2))' },
    neutral: { bg: 'rgb(var(--color-line-100))', fg: 'rgb(var(--color-ink-500))' },
    brand: { bg: 'rgb(var(--color-brand-50))', fg: 'rgb(var(--color-brand-700))' },
};

// Entry status (matches App\Enums\ReportEntryStatus). variant drives the chip color,
// rail drives the left status rail of an entry card.
export const STATE_META = {
    completed: { label: 'Completado', variant: 'success', rail: 'rgb(var(--color-success-2))' },
    partial: { label: 'Parcial', variant: 'warning', rail: 'rgb(var(--color-warning))' },
    not_completed: { label: 'No cumplido', variant: 'danger', rail: 'rgb(var(--color-danger-2))' },
    skipped: { label: 'Omitido', variant: 'info', rail: 'rgb(var(--color-info-2))' },
    pending: { label: 'Pendiente', variant: 'neutral', rail: 'rgb(var(--color-line-300))' },
};

export const STATE_ORDER = ['completed', 'partial', 'not_completed', 'skipped', 'pending'];

// Mood (matches App\Enums\Mood). Color mapping per the design; labels/emoji come from the server.
export const MOOD_VARIANT = {
    great: 'success',
    good: 'brand',
    neutral: 'neutral',
    bad: 'warning',
    terrible: 'danger',
};

export const stateMeta = (status) => STATE_META[status] ?? STATE_META.pending;
export const chipStyle = (variant) => CHIP[variant] ?? CHIP.neutral;
export const moodVariant = (mood) => MOOD_VARIANT[mood] ?? 'neutral';
