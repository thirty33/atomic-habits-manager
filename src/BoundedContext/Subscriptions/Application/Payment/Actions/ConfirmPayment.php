<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Payment\Actions;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\ConfirmPaymentData;
use Core\BoundedContext\Subscriptions\Domain\Payment\PaymentRepository;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentId;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Subscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\SubscriptionRepository;
use Core\Shared\Application\Persistence\TransactionManager;

/**
 * The admin confirmed a notified payment on Binance. Marks the Payment as
 * confirmed (audited with the admin's id) and upgrades the user's subscription
 * to the payment's plan tier (e.g. unlimited) — a plan/subscription field, NOT
 * users.is_active. Both writes happen in one transaction.
 */
final readonly class ConfirmPayment
{
    public function __construct(
        private PaymentRepository $payments,
        private SubscriptionRepository $subscriptions,
        private TransactionManager $transaction,
    ) {}

    public function __invoke(ConfirmPaymentData $data): void
    {
        $admin = UserId::from($data->adminUserId);
        $payment = $this->payments->find(PaymentId::from($data->paymentId));

        $payment->confirm($admin);

        $userId = $payment->userId();
        $subscription = $this->subscriptions->findByUser($userId) ?? Subscription::startFree($userId);
        $subscription->upgradeTo($payment->plan());

        $this->transaction->execute(function () use ($payment, $subscription): void {
            $this->payments->save($payment);
            $this->subscriptions->save($subscription);
        });
    }
}
