<script>
export default { name: 'PlansPage' };
</script>

<script setup>
// Planes view — compares the Free and Unlimited tiers and opens the subscription
// modal. Standalone page (not inside the backoffice layout): owns its own header.
// Hexagonal: delegates loading to usePlans over the injected HTTP gateway.
import { computed, onMounted } from 'vue';
import { HttpSubscriptionGateway } from '@/subscriptions/infrastructure/http/http-subscription-gateway.js';
import { usePlans } from '@/subscriptions/application/use-plans.js';
import PlanCard from '@/components/subscriptions/PlanCard.vue';
import SubscribeModal from '@/components/subscriptions/SubscribeModal.vue';
import { AppSpinner } from '@/components/ui';
import { ref } from 'vue';

const props = defineProps({
    plansJsonUrl: { type: String, required: true },
    notifyPaymentUrl: { type: String, required: true },
    registerUrl: { type: String, required: true },
});

const gateway = new HttpSubscriptionGateway({
    plansJsonUrl: props.plansJsonUrl,
    notifyPaymentUrl: props.notifyPaymentUrl,
    registerUrl: props.registerUrl,
});

const {
    freePlan, unlimitedPlan, binancePaymentEmail, registered, isOnFree, loading, error, load,
} = usePlans(gateway);

const modalOpen = ref(false);
const modalMode = ref('subscribe');

const unlimitedAmount = computed(() =>
    unlimitedPlan.value ? unlimitedPlan.value.formattedPrice : ''
);

function openModal(mode = 'subscribe') {
    modalMode.value = mode;
    modalOpen.value = true;
}

function closeModal() {
    modalOpen.value = false;
}

onMounted(load);
</script>

<template>
    <div class="min-h-screen bg-page-cream">
        <div class="mx-auto max-w-[1000px] px-5 py-8 lg:px-8 lg:py-12">
            <!-- Header -->
            <header>
                <div class="po-eyebrow mb-2">Planes · Átomo</div>
                <h1 class="display text-[40px] lg:text-[52px] text-ink-900">Elige tu plan</h1>
                <p class="mt-3 text-[14px] text-ink-500 leading-relaxed">
                    Empieza gratis. Sube a Unlimited cuando quieras.
                </p>
            </header>

            <div v-if="loading" class="flex justify-center py-20"><AppSpinner /></div>

            <div v-else-if="error" class="mt-8 app-alert danger">
                No pudimos cargar los planes. Intenta de nuevo más tarde.
            </div>

            <template v-else>
                <div class="mt-8 max-w-[920px]">
                    <div class="flex flex-col-reverse gap-5 lg:grid lg:grid-cols-2 lg:gap-6 lg:items-stretch">
                        <PlanCard
                            v-if="freePlan"
                            :plan="freePlan"
                            :current="isOnFree"
                            @action="openModal('register')"
                        />
                        <PlanCard
                            v-if="unlimitedPlan"
                            :plan="unlimitedPlan"
                            @action="openModal('subscribe')"
                        />
                    </div>

                    <!-- Footnote -->
                    <div class="mt-7 flex items-center gap-2 font-mono text-[11.5px] text-ink-400 tracking-[0.02em]">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" />
                        </svg>
                        <span>Pago por cripto (Binance). Activación manual tras verificar el pago.</span>
                    </div>
                </div>
            </template>
        </div>

        <SubscribeModal
            :opened="modalOpen"
            :gateway="gateway"
            :registered="registered"
            :mode="modalMode"
            :binance-email="binancePaymentEmail"
            :amount="unlimitedAmount"
            @close="closeModal"
        />
    </div>
</template>
