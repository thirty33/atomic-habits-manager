<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain;

/**
 * Marker for which Domain Event a Message factory wants the repository
 * to record after id assignment. Internal to the Message aggregate; the
 * outside world never sees these values.
 */
enum PendingFactoryEvent
{
    case UserPosted;
    case AssistantPosted;
}
