<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Infrastructure\Persistence\Eloquent;

use App\Models\Payment as PaymentModel;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Application\Payment\PaymentReader;
use Core\BoundedContext\Subscriptions\Domain\Payment\Exceptions\PaymentNotFound;
use Core\BoundedContext\Subscriptions\Domain\Payment\Payment;
use Core\BoundedContext\Subscriptions\Domain\Payment\PaymentRepository;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\BinanceEmail;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentId;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\PaymentStatus;
use Core\BoundedContext\Subscriptions\Domain\Payment\ValueObjects\TxReference;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Amount;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\Currency;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Core\Shared\Domain\Bus\DomainEventBus;
use DateTimeImmutable;

/**
 * Data Mapper between the Payment aggregate and App\Models\Payment. On insert
 * it assigns the id, records the PaymentNotified event after persisting, then
 * pulls and publishes all accumulated domain events through the bus. The
 * transaction boundary lives in the Application use case (TransactionManager);
 * the publish runs within that outer transaction.
 */
final readonly class EloquentPaymentRepository implements PaymentReader, PaymentRepository
{
    public function __construct(private DomainEventBus $bus) {}

    public function save(Payment $payment): void
    {
        $isNew = ! $payment->hasId();

        $model = $isNew
            ? new PaymentModel
            : PaymentModel::query()->findOrFail($payment->id()->value());

        $model->fill([
            'user_id' => $payment->userId()->value(),
            'plan' => $payment->plan()->value(),
            'amount' => $payment->amount()->value(),
            'currency' => $payment->currency()->value(),
            'payer_binance_email' => $payment->payerBinanceEmail()->value(),
            'tx_reference' => $payment->txReference()?->value(),
            'status' => $payment->status()->value(),
            'notified_at' => $payment->notifiedAt(),
            'confirmed_at' => $payment->confirmedAt(),
            'confirmed_by' => $payment->confirmedBy()?->value(),
        ])->save();

        if ($isNew) {
            $payment->assignId(PaymentId::from((int) $model->getKey()));
            $payment->recordNotifiedAfterAssign();
        }

        $this->bus->publish(...$payment->pullDomainEvents());
    }

    public function find(PaymentId $id): Payment
    {
        $model = PaymentModel::query()->find($id->value());

        if ($model === null) {
            throw PaymentNotFound::withId($id);
        }

        return Payment::fromPrimitives(
            id: PaymentId::from((int) $model->getKey()),
            userId: UserId::from((int) $model->user_id),
            plan: PlanTier::from((string) $model->plan),
            amount: Amount::from((float) $model->amount),
            currency: Currency::from((string) $model->currency),
            payerBinanceEmail: BinanceEmail::from((string) $model->payer_binance_email),
            txReference: TxReference::optional($model->tx_reference !== null ? (string) $model->tx_reference : null),
            status: PaymentStatus::from((string) $model->status),
            notifiedAt: new DateTimeImmutable((string) $model->notified_at),
            confirmedAt: $model->confirmed_at !== null ? new DateTimeImmutable((string) $model->confirmed_at) : null,
            confirmedBy: $model->confirmed_by !== null ? UserId::from((int) $model->confirmed_by) : null,
        );
    }

    public function latestNotifiedPaymentIdForUser(UserId $userId): ?int
    {
        $paymentId = PaymentModel::query()
            ->where('user_id', $userId->value())
            ->where('status', PaymentStatus::PAYMENT_NOTIFIED)
            ->orderByDesc('notified_at')
            ->orderByDesc('payment_id')
            ->value('payment_id');

        return $paymentId !== null ? (int) $paymentId : null;
    }

    public function usersWithNotifiedPayment(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        return PaymentModel::query()
            ->whereIn('user_id', $userIds)
            ->where('status', PaymentStatus::PAYMENT_NOTIFIED)
            ->distinct()
            ->pluck('user_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }
}
