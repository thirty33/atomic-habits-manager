// Subscriptions infrastructure — HTTP adapter implementing the gateway port over
// the bootstrap axios instance. Maps the JSON contract into domain plans and
// normalizes 422 validation errors into { status, errors } for the controller.

import { Plan } from '../../domain/plan.js';

function normalize(e) {
    if (e?.response?.status === 422) {
        return { status: 422, errors: e.response.data.errors ?? {} };
    }
    return e;
}

export class HttpSubscriptionGateway {
    /**
     * @param {{ plansJsonUrl: string, notifyPaymentUrl: string, registerUrl: string }} urls
     */
    constructor({ plansJsonUrl, notifyPaymentUrl, registerUrl }) {
        this.plansJsonUrl = plansJsonUrl;
        this.notifyPaymentUrl = notifyPaymentUrl;
        this.registerUrl = registerUrl;
    }

    async loadPlans() {
        const { data } = await window.axios.get(this.plansJsonUrl);
        return {
            plans: (data.plans ?? []).map((dto) => Plan.fromDto(dto)),
            binancePaymentEmail: data.binance_payment_email ?? '',
            currentTier: data.current_tier ?? 'free',
            // Whether the current user already owns a registered (non-guest) account.
            registered: data.registered ?? false,
        };
    }

    async notifyPayment(payload) {
        try {
            const { data } = await window.axios.post(this.notifyPaymentUrl, payload);
            return { paymentId: data?.extra?.payment_id ?? null };
        } catch (e) {
            throw normalize(e);
        }
    }

    async register(payload) {
        try {
            const { data } = await window.axios.post(this.registerUrl, payload);
            return { userId: data?.extra?.user_id ?? null, email: data?.extra?.email ?? null };
        } catch (e) {
            throw normalize(e);
        }
    }
}
