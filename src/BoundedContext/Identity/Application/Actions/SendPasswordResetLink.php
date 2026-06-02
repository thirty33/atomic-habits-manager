<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Actions;

use Core\BoundedContext\Identity\Application\DTOs\SendPasswordResetLinkData;
use Core\BoundedContext\Identity\Application\Services\PasswordResetBroker;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;

final readonly class SendPasswordResetLink
{
    public function __construct(private PasswordResetBroker $broker) {}

    public function __invoke(SendPasswordResetLinkData $data): string
    {
        return $this->broker->sendLink(EmailAddress::from($data->email));
    }
}
