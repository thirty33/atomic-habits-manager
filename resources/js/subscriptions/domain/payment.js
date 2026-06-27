// Subscriptions domain — payment notification value object.
// Represents the data the user submits when notifying a Binance transfer.
// Pure, no I/O.

export const PaymentStatus = Object.freeze({
    Form: 'form',
    Loading: 'loading',
    Success: 'success',
    Error: 'error',
});

export class PaymentNotification {
    constructor({ payerBinanceEmail = '', txReference = '', planTier = 'unlimited' } = {}) {
        this.payerBinanceEmail = payerBinanceEmail;
        this.txReference = txReference;
        this.planTier = planTier;
    }

    /**
     * Maps to the snake_case payload the notify-payment endpoint expects.
     */
    toPayload() {
        const payload = {
            payer_binance_email: this.payerBinanceEmail,
            plan_tier: this.planTier,
        };
        if (this.txReference) {
            payload.tx_reference = this.txReference;
        }
        return payload;
    }
}
