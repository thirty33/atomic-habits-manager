<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Payment\Actions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\RejectPaymentData;
use Core\BoundedContext\Subscriptions\Domain\Payment\PaymentRepository;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentId;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Subscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\SubscriptionRepository;
use Core\Shared\Application\Persistence\TransactionManager;

/**
 * The admin could not reconcile a notified payment and rejects it. Marks the
 * Payment as rejected (audited) and returns the user's subscription to the
 * active state on its current tier. Both writes happen in one transaction.
 */
final readonly class RejectPayment
{
    public function __construct(
        private PaymentRepository $payments,
        private SubscriptionRepository $subscriptions,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(RejectPaymentData $data): void
    {
        $admin = UserId::from($data->adminUserId);
        $payment = $this->payments->find(PaymentId::from($data->paymentId));

        $payment->reject($admin);

        $userId = $payment->userId();
        $subscription = $this->subscriptions->findByUser($userId) ?? Subscription::startFree($userId);
        $subscription->returnToActive();

        $this->transaction->execute(function () use ($payment, $subscription): void {
            $this->payments->save($payment);
            $this->subscriptions->save($subscription);
        });
    }
}
