<?php

declare(strict_types=1);

namespace Core\BoundedContext\Insights\Application;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Insights\Application\ReadModels\InsightSnapshot;

/**
 * Read-side port for dashboard insights. CQRS counterpart of InsightRepository.
 */
interface InsightReader
{
    /**
     * The user's most recently generated insight, or null if none exists yet.
     */
    public function latestForUser(UserId $userId): ?InsightSnapshot;
}
