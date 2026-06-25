// Subscriptions application controller — loads the plan catalog.
// The Plans page stays thin and delegates here; data access is behind the
// injected gateway port (see infrastructure/http/HttpSubscriptionGateway).

import { computed, ref } from 'vue';
import { PlanTier } from '../domain/plan.js';

export function usePlans(gateway) {
    const plans = ref([]);
    const binancePaymentEmail = ref('');
    const currentTier = ref(PlanTier.Free);
    const registered = ref(true);
    const loading = ref(false);
    const error = ref(null);

    const freePlan = computed(() => plans.value.find((p) => p.isFree) ?? null);
    const unlimitedPlan = computed(() => plans.value.find((p) => p.isUnlimited) ?? null);
    const isOnFree = computed(() => currentTier.value === PlanTier.Free);

    async function load() {
        loading.value = true;
        error.value = null;
        try {
            const result = await gateway.loadPlans();
            plans.value = result.plans;
            binancePaymentEmail.value = result.binancePaymentEmail;
            currentTier.value = result.currentTier;
            registered.value = result.registered;
        } catch (e) {
            error.value = e;
        } finally {
            loading.value = false;
        }
    }

    return {
        plans, freePlan, unlimitedPlan,
        binancePaymentEmail, currentTier, registered, isOnFree,
        loading, error, load,
    };
}
