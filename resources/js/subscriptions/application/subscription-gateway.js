// Subscriptions application — gateway PORT.
// loadPlans(): Promise<{ plans: Plan[], binancePaymentEmail: string, currentTier: string, registered: boolean }>
// notifyPayment(payload): Promise<{ paymentId }>   (throws { status: 422, errors } on validation failure)
// register(payload): Promise<{ userId, email }>     (throws { status: 422, errors } on validation failure)

export const isSubscriptionGateway = (g) =>
    !!g &&
    typeof g.loadPlans === 'function' &&
    typeof g.notifyPayment === 'function' &&
    typeof g.register === 'function';
