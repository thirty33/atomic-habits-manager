<script>
export default { name: 'SubscribeModal' };
</script>

<script setup>
// Subscription modal — reuses the AppModal shell and renders the 4 states of the
// useSubscribe controller (form | loading | success | error). The Registro block
// only renders for unregistered guests; the Pago block is always shown. Per-field
// 422 errors are surfaced under each input. Light-only, single `lg` breakpoint.
import { computed } from 'vue';
import { AppModal } from '@/components/ui';
import AppAlert from './AppAlert.vue';
import PayCard from './PayCard.vue';
import { useSubscribe } from '@/subscriptions/application/use-subscribe.js';

const props = defineProps({
    opened: {
        type: Boolean,
        default: false,
    },
    gateway: {
        type: Object,
        required: true,
    },
    registered: {
        type: Boolean,
        default: true,
    },
    // 'subscribe' = claim + Binance payment (Unlimited); 'register' = claim only,
    // no payment (Free plan's "Asegurar mi cuenta").
    mode: {
        type: String,
        default: 'subscribe',
    },
    binanceEmail: {
        type: String,
        default: '',
    },
    amount: {
        type: String,
        default: '',
    },
    // Where to send the user after a successful submit (back into the app).
    dashboardUrl: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close']);

const isRegisterMode = computed(() => props.mode === 'register');

const {
    fields, copied, isRegistered,
    isForm, isLoading, isSuccess, isError,
    fieldError, submit, reset, copyBinanceEmail,
} = useSubscribe(props.gateway, { registered: () => props.registered, mode: () => props.mode });

function close() {
    emit('close');
}

function acknowledge() {
    reset();
    emit('close');
    // After a successful subscribe/register, take the user back into the app
    // (the Plans page is standalone, with no sidebar to navigate away).
    if (props.dashboardUrl) {
        window.location.href = props.dashboardUrl;
        return;
    }
}

function onCopy() {
    copyBinanceEmail(props.binanceEmail);
}
</script>

<template>
    <AppModal :opened="opened" max-width-class="max-w-[580px]" @close="close">
        <template #content>
            <!-- Header -->
            <header class="px-8 py-5 flex items-center justify-between border-b border-line-200 shrink-0">
                <div v-if="isRegisterMode">
                    <div class="po-eyebrow mb-1">Plan Free</div>
                    <h2 class="font-serif text-[24px] leading-none">Asegura tu cuenta</h2>
                </div>
                <div v-else>
                    <div class="po-eyebrow mb-1">Suscripción · Unlimited</div>
                    <h2 class="font-serif text-[24px] leading-none">Completa tu suscripción</h2>
                </div>
            </header>

            <!-- Body: success state -->
            <div v-if="isSuccess" class="px-8 py-12 text-center flex flex-col items-center">
                <span class="w-16 h-16 rounded-full grid place-items-center bg-[#e5f1ec] text-success-2">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12" />
                    </svg>
                </span>
                <h2 class="font-serif text-[32px] mt-5 leading-none">{{ isRegisterMode ? '¡Cuenta creada!' : '¡Pago notificado!' }}</h2>
                <p class="text-[14px] text-ink-500 mt-3 max-w-[360px] leading-relaxed">
                    <template v-if="isRegisterMode">
                        Tu cuenta quedó registrada y activa. Revisa tu correo para confirmarla; ya puedes seguir usando la app.
                    </template>
                    <template v-else>
                        Te activaremos en cuanto verifiquemos el pago. Revisa tu correo para confirmar tu cuenta.
                    </template>
                </p>
            </div>

            <!-- Body: form / loading / error states -->
            <div v-else class="relative">
                <div :class="['px-8 py-6 space-y-6', isLoading ? 'opacity-60 pointer-events-none select-none' : '']">
                    <AppAlert
                        v-if="isError"
                        variant="danger"
                        title="No pudimos procesar tu notificación"
                        message="Revisa los campos marcados e inténtalo de nuevo."
                    />

                    <!-- Registro block (only for unregistered guests) -->
                    <div v-if="!isRegistered">
                        <div class="po-eyebrow mb-3">Crea tu cuenta</div>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5">
                            <div class="lg:col-span-2">
                                <label class="field-label">Nombre</label>
                                <input
                                    v-model="fields.name"
                                    :class="['field-input mt-1.5', fieldError('name') ? 'err' : '']"
                                    placeholder="Ej: Valentina Rojas"
                                />
                                <div v-if="fieldError('name')" class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" /></svg>
                                    {{ fieldError('name') }}
                                </div>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="field-label">Email</label>
                                <input
                                    v-model="fields.email"
                                    :class="['field-input mt-1.5', fieldError('email') ? 'err' : '']"
                                    placeholder="tu@correo.com"
                                />
                                <div v-if="fieldError('email')" class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" /></svg>
                                    {{ fieldError('email') }}
                                </div>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="field-label">Contraseña</label>
                                <input
                                    v-model="fields.password"
                                    type="password"
                                    :class="['field-input mt-1.5', fieldError('password') ? 'err' : '']"
                                    placeholder="Mínimo 8 caracteres"
                                />
                                <div v-if="fieldError('password')" class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" /></svg>
                                    {{ fieldError('password') }}
                                </div>
                            </div>
                        </div>
                        <p class="text-[12.5px] text-ink-500 mt-2.5 leading-snug">
                            Te enviaremos un enlace de confirmación a tu correo (no bloquea el uso de tu cuenta).
                            Esto reclama tu cuenta invitada y conserva tus hábitos.
                        </p>
                    </div>

                    <!-- Register-only mode, already-registered user: nothing to do. -->
                    <p v-if="isRegisterMode && isRegistered" class="text-[14px] text-ink-500 leading-relaxed">
                        Tu cuenta ya está registrada y asegurada. No necesitas hacer nada más en el plan Free.
                    </p>

                    <div v-if="!isRegistered && !isRegisterMode" class="h-px bg-line-200" />

                    <!-- Pago block (hidden in register-only mode) -->
                    <div v-if="!isRegisterMode">
                        <div class="po-eyebrow mb-3">Pago · Binance</div>

                        <PayCard
                            :binance-email="binanceEmail"
                            :amount="amount"
                            :copied="copied"
                            @copy="onCopy"
                        />

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3.5 mt-4">
                            <div class="lg:col-span-2">
                                <label class="field-label">Tu correo de Binance</label>
                                <input
                                    v-model="fields.payerBinanceEmail"
                                    :class="['field-input mt-1.5', fieldError('payer_binance_email') ? 'err' : '']"
                                    placeholder="correo-desde-el-que-pagaste@binance.com"
                                />
                                <div v-if="fieldError('payer_binance_email')" class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" /></svg>
                                    {{ fieldError('payer_binance_email') }}
                                </div>
                            </div>
                            <div class="lg:col-span-2">
                                <label class="field-label">
                                    Referencia / Hash de la transacción
                                    <span class="text-ink-400 font-normal">· opcional</span>
                                </label>
                                <input
                                    v-model="fields.txReference"
                                    :class="['field-input mt-1.5 font-mono !text-[13px]', fieldError('tx_reference') ? 'err' : '']"
                                    placeholder="TxID o referencia de Binance"
                                />
                                <div v-if="fieldError('tx_reference')" class="field-error">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><line x1="12" y1="16" x2="12" y2="12" /><line x1="12" y1="8" x2="12.01" y2="8" /></svg>
                                    {{ fieldError('tx_reference') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Loading overlay -->
                <div v-if="isLoading" class="absolute inset-0 grid place-items-center">
                    <div class="flex items-center gap-2.5 rounded-full bg-card border border-line-200 px-4 py-2 shadow-sm text-[13px] text-ink-700">
                        <span class="spinner text-brand-700" /> Enviando…
                    </div>
                </div>
            </div>

            <!-- Footer: success state -->
            <footer
                v-if="isSuccess"
                class="px-8 py-5 bg-paper border-t border-line-200 flex items-center justify-center shrink-0"
            >
                <button class="btn btn-outline !text-[13.5px] !px-7" type="button" @click="acknowledge">Entendido</button>
            </footer>

            <!-- Footer: form / loading / error states -->
            <footer
                v-else
                class="px-8 py-5 bg-paper border-t border-line-200 flex items-center justify-between gap-3 shrink-0"
            >
                <span class="text-[11.5px] text-ink-400 font-mono flex items-center gap-1.5 max-w-[230px] leading-snug">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <polyline points="9 12 11 14 15 10" />
                    </svg>
                    <template v-if="isRegisterMode">Te enviaremos un correo de confirmación.</template>
                    <template v-else>Verificamos tu pago manualmente. Suele tomar pocas horas.</template>
                </span>
                <div class="flex items-center gap-2">
                    <button class="btn btn-ghost !text-[13.5px]" type="button" :disabled="isLoading" @click="close">Cancelar</button>
                    <button
                        v-if="isLoading"
                        class="btn btn-primary !text-[13.5px] is-disabled"
                        type="button"
                        disabled
                    >
                        <span class="spinner" /> Enviando…
                    </button>
                    <button
                        v-else-if="isRegisterMode && isRegistered"
                        class="btn btn-outline !text-[13.5px]"
                        type="button"
                        @click="close"
                    >
                        Entendido
                    </button>
                    <button v-else class="btn btn-primary !text-[13.5px]" type="button" @click="submit">
                        {{ isRegisterMode ? 'Asegurar mi cuenta' : 'Notificar pago' }}
                    </button>
                </div>
            </footer>
        </template>
    </AppModal>
</template>
