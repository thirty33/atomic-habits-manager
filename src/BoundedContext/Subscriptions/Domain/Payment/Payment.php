<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Domain\Payment;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Domain\Payment\Events\PaymentConfirmed;
use Core\BoundedContext\Subscriptions\Domain\Payment\Events\PaymentNotified;
use Core\BoundedContext\Subscriptions\Domain\Payment\Events\PaymentRejected;
use Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions\PaymentTransitionNotAllowed;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\BinanceEmail;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentId;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentStatus;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\TxReference;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Amount;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Currency;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\Shared\Domain\AggregateRoot;
use DateTimeImmutable;
use LogicException;

/**
 * Aggregate Root for a manual crypto (Binance) payment. Models the
 * reconciliation flow the admin follows:
 *
 *   notify()  → status payment_notified, records PaymentNotified
 *   confirm() → status confirmed, records PaymentConfirmed (audited)
 *   reject()  → status rejected, records PaymentRejected (audited)
 *
 * Events are recorded here; the repository pulls and publishes them. Pure
 * domain: no Eloquent.
 */
final class Payment extends AggregateRoot
{
    private function __construct(
        private ?PaymentId $id,
        private UserId $userId,
        private PlanTier $plan,
        private Amount $amount,
        private Currency $currency,
        private BinanceEmail $payerBinanceEmail,
        private ?TxReference $txReference,
        private PaymentStatus $status,
        private DateTimeImmutable $notifiedAt,
        private ?DateTimeImmutable $confirmedAt,
        private ?UserId $confirmedBy,
    ) {}

    /**
     * Open a payment in the "payment notified" state — the user reported they
     * paid; the admin must still reconcile it on Binance.
     */
    public static function notify(
        UserId $userId,
        PlanTier $plan,
        Amount $amount,
        Currency $currency,
        BinanceEmail $payerBinanceEmail,
        ?TxReference $txReference = null,
    ): self {
        return new self(
            id: null,
            userId: $userId,
            plan: $plan,
            amount: $amount,
            currency: $currency,
            payerBinanceEmail: $payerBinanceEmail,
            txReference: $txReference,
            status: PaymentStatus::paymentNotified(),
            notifiedAt: new DateTimeImmutable,
            confirmedAt: null,
            confirmedBy: null,
        );
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function fromPrimitives(
        PaymentId $id,
        UserId $userId,
        PlanTier $plan,
        Amount $amount,
        Currency $currency,
        BinanceEmail $payerBinanceEmail,
        ?TxReference $txReference,
        PaymentStatus $status,
        DateTimeImmutable $notifiedAt,
        ?DateTimeImmutable $confirmedAt,
        ?UserId $confirmedBy,
    ): self {
        return new self(
            id: $id,
            userId: $userId,
            plan: $plan,
            amount: $amount,
            currency: $currency,
            payerBinanceEmail: $payerBinanceEmail,
            txReference: $txReference,
            status: $status,
            notifiedAt: $notifiedAt,
            confirmedAt: $confirmedAt,
            confirmedBy: $confirmedBy,
        );
    }

    /**
     * The admin verified the transfer on Binance and approves the upgrade.
     */
    public function confirm(UserId $adminUserId): void
    {
        if (! $this->status->isPaymentNotified()) {
            throw PaymentTransitionNotAllowed::from($this->status, 'confirm');
        }

        $this->status = PaymentStatus::confirmed();
        $this->confirmedAt = new DateTimeImmutable;
        $this->confirmedBy = $adminUserId;

        $this->record(PaymentConfirmed::now($this->id(), $this->userId->value(), $adminUserId->value()));
    }

    /**
     * The admin could not match the transfer and rejects it.
     */
    public function reject(UserId $adminUserId): void
    {
        if (! $this->status->isPaymentNotified()) {
            throw PaymentTransitionNotAllowed::from($this->status, 'reject');
        }

        $this->status = PaymentStatus::rejected();

        $this->record(PaymentRejected::now($this->id(), $this->userId->value(), $adminUserId->value()));
    }

    /**
     * Records the "payment notified" event AFTER the repository has assigned the
     * DB-generated id. Deliberate pattern, not a leak: with auto-increment PKs the
     * id does not exist when notify() runs, so the creation event cannot be
     * recorded in the factory. The repository calls this once, right after
     * assignId(), then pulls and publishes.
     */
    public function recordNotifiedAfterAssign(): void
    {
        $this->record(PaymentNotified::now($this->id(), $this->userId->value(), $this->plan->value()));
    }

    public function assignId(PaymentId $id): void
    {
        if ($this->id !== null) {
            throw new LogicException('Payment already has an id.');
        }

        $this->id = $id;
    }

    public function id(): PaymentId
    {
        return $this->id ?? throw new LogicException('Payment has not been persisted yet.');
    }

    public function hasId(): bool
    {
        return $this->id !== null;
    }

    public function userId(): UserId
    {
        return $this->userId;
    }

    public function plan(): PlanTier
    {
        return $this->plan;
    }

    public function amount(): Amount
    {
        return $this->amount;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function payerBinanceEmail(): BinanceEmail
    {
        return $this->payerBinanceEmail;
    }

    public function txReference(): ?TxReference
    {
        return $this->txReference;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function notifiedAt(): DateTimeImmutable
    {
        return $this->notifiedAt;
    }

    public function confirmedAt(): ?DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function confirmedBy(): ?UserId
    {
        return $this->confirmedBy;
    }
}
