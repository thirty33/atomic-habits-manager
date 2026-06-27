<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Payment\Actions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\NotifyPaymentData;
use Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions\PaymentTierNotPayable;
use Core\BoundedContext\Subscriptions\Domain\Payment\Payment;
use Core\BoundedContext\Subscriptions\Domain\Payment\PaymentRepository;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\BinanceEmail;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\TxReference;
use Core\BoundedContext\Subscriptions\Domain\Plan\PlanRepository;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Subscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\SubscriptionRepository;
use Core\Shared\Application\Persistence\TransactionManager;

/**
 * The user reports a crypto payment for an upgrade. Creates a Payment in the
 * "payment notified" state (with the expected amount/currency taken from the
 * plan catalog) and flips the user's subscription to "payment notified" so the
 * admin can reconcile it. Both writes happen in one transaction.
 */
final readonly class NotifyPayment
{
    public function __construct(
        private PaymentRepository $payments,
        private PlanRepository $plans,
        private SubscriptionRepository $subscriptions,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(NotifyPaymentData $data): int
    {
        $userId = UserId::from($data->userId);
        $tier = PlanTier::from($data->planTier);

        if ($tier->isFree()) {
            throw PaymentTierNotPayable::forTier($tier);
        }

        $plan = $this->plans->findByTier($tier);

        if ($plan->amount()->value() <= 0.0) {
            throw PaymentTierNotPayable::forTier($tier);
        }

        $payment = Payment::notify(
            userId: $userId,
            plan: $tier,
            amount: $plan->amount(),
            currency: $plan->currency(),
            payerBinanceEmail: BinanceEmail::from($data->payerBinanceEmail),
            txReference: TxReference::optional($data->txReference),
        );

        $subscription = $this->subscriptions->findByUser($userId) ?? Subscription::startFree($userId);
        $subscription->markPaymentNotified();

        $this->transaction->execute(function () use ($payment, $subscription): void {
            $this->payments->save($payment);
            $this->subscriptions->save($subscription);
        });

        return $payment->id()->value();
    }
}
