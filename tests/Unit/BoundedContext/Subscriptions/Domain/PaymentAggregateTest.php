<?php

declare(strict_types=1);

namespace Tests\Unit\BoundedContext\Subscriptions\Domain;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Domain\Payment\Events\PaymentConfirmed;
use Core\BoundedContext\Subscriptions\Domain\Payment\Events\PaymentNotified;
use Core\BoundedContext\Subscriptions\Domain\Payment\Events\PaymentRejected;
use Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions\PaymentTransitionNotAllowed;
use Core\BoundedContext\Subscriptions\Domain\Payment\Payment;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\BinanceEmail;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentId;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentStatus;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Amount;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Currency;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use PHPUnit\Framework\TestCase;

class PaymentAggregateTest extends TestCase
{
    private function notifiedPayment(): Payment
    {
        return Payment::notify(
            userId: UserId::from(1),
            plan: PlanTier::unlimited(),
            amount: Amount::from(5.0),
            currency: Currency::from('USDT'),
            payerBinanceEmail: BinanceEmail::from('payer@binance.com'),
        );
    }

    public function test_notify_opens_in_payment_notified_status(): void
    {
        $payment = $this->notifiedPayment();

        $this->assertSame(PaymentStatus::PAYMENT_NOTIFIED, $payment->status()->value());
        $this->assertNull($payment->confirmedAt());
        $this->assertNull($payment->confirmedBy());
    }

    public function test_notify_records_event_only_after_id_assigned(): void
    {
        $payment = $this->notifiedPayment();
        $this->assertSame([], $payment->peekDomainEvents());

        $payment->assignId(PaymentId::from(10));
        $payment->recordNotifiedAfterAssign();

        $events = $payment->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PaymentNotified::class, $events[0]);
    }

    public function test_confirm_moves_to_confirmed_and_records_event(): void
    {
        $payment = $this->notifiedPayment();
        $payment->assignId(PaymentId::from(10));

        $payment->confirm(UserId::from(99));

        $this->assertTrue($payment->status()->isConfirmed());
        $this->assertNotNull($payment->confirmedAt());
        $this->assertSame(99, $payment->confirmedBy()?->value());

        $events = $payment->pullDomainEvents();
        $this->assertInstanceOf(PaymentConfirmed::class, $events[0]);
    }

    public function test_reject_moves_to_rejected_and_records_event(): void
    {
        $payment = $this->notifiedPayment();
        $payment->assignId(PaymentId::from(10));

        $payment->reject(UserId::from(99));

        $this->assertTrue($payment->status()->isRejected());

        $events = $payment->pullDomainEvents();
        $this->assertInstanceOf(PaymentRejected::class, $events[0]);
    }

    public function test_confirm_after_confirm_is_not_allowed(): void
    {
        $payment = $this->notifiedPayment();
        $payment->assignId(PaymentId::from(10));
        $payment->confirm(UserId::from(99));

        $this->expectException(PaymentTransitionNotAllowed::class);
        $payment->confirm(UserId::from(99));
    }

    public function test_reject_after_confirm_is_not_allowed(): void
    {
        $payment = $this->notifiedPayment();
        $payment->assignId(PaymentId::from(10));
        $payment->confirm(UserId::from(99));

        $this->expectException(PaymentTransitionNotAllowed::class);
        $payment->reject(UserId::from(99));
    }
}
