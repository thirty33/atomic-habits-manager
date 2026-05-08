<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Logging;

use Core\Shared\Application\Logging\Logger;
use Psr\Log\LoggerInterface;

/**
 * Laravel adapter for the Application Logger port. Writes through the
 * configured Laravel log channel — the channel choice (single, stack,
 * stderr, etc.) belongs in `config/logging.php`, not here.
 *
 * Constructor takes the framework's PSR-3 logger so the adapter is
 * trivially swappable in tests with an in-memory implementation.
 */
final readonly class LaravelLogger implements Logger
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
}
