<?php

declare(strict_types=1);

namespace Core\BoundedContext\Insights\Application\ReadModels;

/**
 * Read model for the latest dashboard insight of a user.
 */
final readonly class InsightSnapshot
{
    public function __construct(
        public string $message,
        public string $generatedAt,
    ) {}
}
