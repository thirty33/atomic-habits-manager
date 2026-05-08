<?php

declare(strict_types=1);

namespace App\Providers;

use Core\BoundedContext\Conversations\Domain\ConversationRepository;
use Core\BoundedContext\Conversations\Infrastructure\Persistence\Eloquent\EloquentConversationRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Bindings of the Conversations BC Domain layer to its concrete Eloquent
 * adapters, plus registration of cross-BC subscriptions for the Domain
 * Event bus.
 *
 * Keeps Application ignorant of which adapter is plugged in.
 */
final class ConversationServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        ConversationRepository::class => EloquentConversationRepository::class,
    ];
}
