// Subscriptions domain — Plan model.
// Mirrors the backend PlanReader::planInfo() shape and exposes the editorial
// feature copy each tier card renders. Pure, no I/O.

export const PlanTier = Object.freeze({
    Free: 'free',
    Unlimited: 'unlimited',
});

/**
 * Static feature copy per tier (the marketing list rendered on the cards).
 * Each item: { kind: 'on' | 'off' | 'star', label, pro? }.
 */
const FEATURES = Object.freeze({
    [PlanTier.Free]: [
        { kind: 'on', label: 'Hasta 3 hábitos' },
        { kind: 'on', label: 'Módulos: Hábitos, Calendario, Reportes' },
        { kind: 'off', label: 'Atomic IA' },
        { kind: 'off', label: 'Hábitos ilimitados' },
    ],
    [PlanTier.Unlimited]: [
        { kind: 'on', label: 'Hábitos ilimitados' },
        { kind: 'on', label: 'Todos los módulos' },
        { kind: 'star', label: 'Atomic IA — tu asistente de hábitos', pro: true },
        { kind: 'on', label: 'Soporte prioritario' },
    ],
});

export class Plan {
    constructor({ tier, amount, currency, modules, maxHabits }) {
        this.tier = tier;
        this.amount = amount;
        this.currency = currency;
        this.modules = modules ?? [];
        this.maxHabits = maxHabits ?? null;
    }

    static fromDto(dto) {
        return new Plan({
            tier: dto.tier,
            amount: dto.amount,
            currency: dto.currency,
            modules: dto.modules,
            maxHabits: dto.max_habits,
        });
    }

    get isFree() {
        return this.tier === PlanTier.Free;
    }

    get isUnlimited() {
        return this.tier === PlanTier.Unlimited;
    }

    get features() {
        return FEATURES[this.tier] ?? [];
    }

    /**
     * Display price. Free renders as "Gratis"; paid tiers render the formatted
     * amount with currency (e.g. "9.99 USDT").
     */
    get formattedPrice() {
        if (this.isFree) {
            return 'Gratis';
        }
        const amount = Number.isInteger(this.amount) ? this.amount : this.amount.toFixed(2);
        return `${amount} ${this.currency}`;
    }
}
