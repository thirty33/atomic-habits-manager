<?php

declare(strict_types=1);

namespace Core\Shared\Application\Logging;

/**
 * Application port for structured logging. Allows Application services
 * (Use Cases, pipes, listeners) to emit observability events without
 * coupling to a specific framework logger. Concrete implementations
 * live in Infrastructure (e.g. Laravel Log facade adapter).
 */
interface Logger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * @param  array<string, mixed>  $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * @param  array<string, mixed>  $context
     */
    public function error(string $message, array $context = []): void;
}
