// Subscriptions application controller — drives the subscription modal.
// Holds the form field values + per-field 422 errors and the modal state machine
// (form | loading | success | error). The Vue component stays thin and delegates
// every interaction here; I/O goes through the injected gateway port.

import { computed, reactive, ref, watch } from 'vue';
import { PaymentStatus } from '../domain/payment.js';

export function useSubscribe(gateway, { registered = true, mode = 'subscribe' } = {}) {
    // `registered` may be a plain boolean or a getter (so the modal can react to
    // the async-loaded /plans/json `registered` flag). Read it through this.
    const readRegistered = () => (typeof registered === 'function' ? registered() : registered);
    // `mode` ('subscribe' = claim + notify Binance payment; 'register' = claim
    // only, no payment — used by the Free plan's "Asegurar mi cuenta"). May be a
    // getter so the modal can switch mode between Free and Unlimited CTAs.
    const readMode = () => (typeof mode === 'function' ? mode() : mode);

    const state = ref(PaymentStatus.Form);
    const copied = ref(false);

    // Field values bound to the form. The registro fields are only used when the
    // current user is an unregistered guest claiming their account.
    const fields = reactive({
        name: '',
        email: '',
        password: '',
        payerBinanceEmail: '',
        txReference: '',
    });

    // Per-field validation errors keyed by the backend field name. The Pago
    // fields map by their snake_case keys (payer_binance_email, tx_reference),
    // the Registro fields by name/email/password.
    const errors = reactive({});

    const isRegistered = ref(readRegistered());
    // Adopt the source value once /plans/json resolves (guest → false). A claim
    // inside submit() sets this to true locally; the source never flips back.
    watch(readRegistered, (value) => {
        isRegistered.value = value;
    });
    const isLoading = computed(() => state.value === PaymentStatus.Loading);
    const isForm = computed(() => state.value === PaymentStatus.Form);
    const isSuccess = computed(() => state.value === PaymentStatus.Success);
    const isError = computed(() => state.value === PaymentStatus.Error);

    function fieldError(key) {
        const messages = errors[key];
        return Array.isArray(messages) ? messages[0] : messages ?? null;
    }

    function clearErrors() {
        Object.keys(errors).forEach((key) => delete errors[key]);
    }

    function applyErrors(serverErrors) {
        clearErrors();
        Object.entries(serverErrors ?? {}).forEach(([key, value]) => {
            errors[key] = value;
        });
    }

    async function copyBinanceEmail(email) {
        if (!email) {
            return;
        }
        try {
            await navigator.clipboard.writeText(email);
            copied.value = true;
            setTimeout(() => {
                copied.value = false;
            }, 1600);
        } catch (e) {
            copied.value = false;
        }
    }

    /**
     * Submit the subscription flow: claim the guest account first (when the user
     * is unregistered) and then notify the Binance payment. On any 422 the state
     * flips to error and the per-field messages are surfaced.
     */
    async function submit() {
        state.value = PaymentStatus.Loading;
        clearErrors();
        try {
            if (!isRegistered.value) {
                await gateway.register({
                    name: fields.name,
                    email: fields.email,
                    password: fields.password,
                    password_confirmation: fields.password,
                });
                isRegistered.value = true;
            }

            // Register-only mode (Free plan) stops after claiming the account; no
            // Binance payment is notified.
            if (readMode() !== 'register') {
                const payload = {
                    payer_binance_email: fields.payerBinanceEmail,
                    plan_tier: 'unlimited',
                };
                if (fields.txReference) {
                    payload.tx_reference = fields.txReference;
                }
                await gateway.notifyPayment(payload);
            }

            state.value = PaymentStatus.Success;
        } catch (e) {
            if (e?.status === 422) {
                applyErrors(e.errors);
            }
            state.value = PaymentStatus.Error;
        }
    }

    function reset() {
        state.value = PaymentStatus.Form;
        clearErrors();
        fields.name = '';
        fields.email = '';
        fields.password = '';
        fields.payerBinanceEmail = '';
        fields.txReference = '';
    }

    return {
        state, fields, errors, copied, isRegistered,
        isLoading, isForm, isSuccess, isError,
        fieldError, submit, reset, copyBinanceEmail,
    };
}
