<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\DTOs;

final readonly class AuthenticateUserData
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $remember,
        public string $ipAddress,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: (string) $data['email'],
            password: (string) $data['password'],
            remember: (bool) ($data['remember'] ?? false),
            ipAddress: (string) $data['ip_address'],
        );
    }
}
