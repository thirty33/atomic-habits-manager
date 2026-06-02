<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\DTOs;

final readonly class SendPasswordResetLinkData
{
    public function __construct(public string $email) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(email: (string) $data['email']);
    }
}
