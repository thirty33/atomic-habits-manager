<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Payment\DTOs;

final readonly class NotifyPaymentData
{
    public function __construct(
        public int $userId,
        public string $planTier,
        public string $payerBinanceEmail,
        public ?string $txReference = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(int $userId, array $data): self
    {
        return new self(
            userId: $userId,
            planTier: (string) ($data['plan_tier'] ?? ''),
            payerBinanceEmail: (string) ($data['payer_binance_email'] ?? ''),
            txReference: isset($data['tx_reference']) ? (string) $data['tx_reference'] : null,
        );
    }
}
