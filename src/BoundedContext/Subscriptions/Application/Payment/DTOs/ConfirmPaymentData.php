<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Payment\DTOs;

final readonly class ConfirmPaymentData
{
    public function __construct(
        public int $paymentId,
        public int $adminUserId,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            paymentId: (int) ($data['payment_id'] ?? 0),
            adminUserId: (int) ($data['admin_user_id'] ?? 0),
        );
    }
}
