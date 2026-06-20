// Calendar domain — status & accent semantics (single source of color truth).
// Values use the project's live redesign tokens: the CSS variables hold RGB
// triplets, so colors are written as rgb(var(--color-x) / <alpha>).
// Status owns the block background; accent (habit color) only tints the border.

export const STATUS = {
    done: { label: 'Completado', bg: 'rgb(var(--color-brand-700))', fg: '#f4ead6', border: 'transparent' },
    pending: { label: 'Programado', bg: 'rgb(var(--color-card))', fg: 'rgb(var(--color-ink-700))', border: 'rgb(var(--color-line-300))' },
    partial: { label: 'Parcial', bg: 'rgb(var(--color-warning))', fg: '#3a2c0b', border: 'transparent' },
    missed: { label: 'No cumplido', bg: 'rgb(var(--color-danger-2) / 0.12)', fg: 'rgb(var(--color-danger-2))', border: 'rgb(var(--color-danger-2))' },
    skipped: { label: 'Omitido', bg: 'rgb(var(--color-line-100))', fg: 'rgb(var(--color-ink-500))', border: 'rgb(var(--color-line-300))' },
};

export const LEGEND = ['done', 'pending', 'partial', 'missed', 'skipped'];

export const ACCENT = {
    brand: 'rgb(var(--color-brand-700))',
    info: 'rgb(var(--color-info-2))',
    warning: 'rgb(var(--color-warning))',
    success: 'rgb(var(--color-success-2))',
    danger: 'rgb(var(--color-danger-2))',
};

export const NOW_RING = 'rgb(var(--color-brand-300))';

export const statusStyle = (status) => STATUS[status] ?? STATUS.pending;
export const statusLabel = (status) => statusStyle(status).label;
export const accentColor = (accent) => ACCENT[accent] ?? ACCENT.brand;
