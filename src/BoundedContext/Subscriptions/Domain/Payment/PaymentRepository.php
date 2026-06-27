<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Payment;

use Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions\PaymentNotFound;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentId;

/**
 * Write-side contract for payments. Bare DB operations only — the transaction
 * boundary lives in the Application use case (TransactionManager), never here.
 */
interface PaymentRepository
{
    public function save(Payment $payment): void;

    /** @throws PaymentNotFound */
    public function find(PaymentId $id): Payment;
}
