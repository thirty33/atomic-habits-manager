<?php

declare(strict_types=1);

namespace App\Providers;

use Core\BoundedContext\Subscriptions\Application\Payment\PaymentReader;
use Core\BoundedContext\Subscriptions\Application\Plan\PlanCatalogReader;
use Core\BoundedContext\Subscriptions\Domain\Payment\Events\PaymentConfirmed;
use Core\BoundedContext\Subscriptions\Domain\Payment\Events\PaymentNotified;
use Core\BoundedContext\Subscriptions\Domain\Payment\Events\PaymentRejected;
use Core\BoundedContext\Subscriptions\Domain\Payment\PaymentRepository;
use Core\BoundedContext\Subscriptions\Domain\Plan\PlanRepository;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Events\PaymentWasNotifiedForSubscription;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Events\SubscriptionReturnedToActive;
use Core\BoundedContext\Subscriptions\Domain\Subscription\Events\SubscriptionWasUpgraded;
use Core\BoundedContext\Subscriptions\Domain\Subscription\SubscriptionRepository;
use Core\BoundedContext\Subscriptions\Infrastructure\Persistence\Eloquent\EloquentPaymentRepository;
use Core\BoundedContext\Subscriptions\Infrastructure\Persistence\Eloquent\EloquentPlanRepository;
use Core\BoundedContext\Subscriptions\Infrastructure\Persistence\Eloquent\EloquentSubscriptionRepository;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventClassRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Binds the Subscriptions bounded context contracts (Plan, Subscription,
 * Payment) to their Eloquent implementations. Use cases receive these via
 * constructor auto-resolution — no app()/resolve() in the domain/application.
 *
 * The PlanPolicy/PlanLimits/PlanModules domain services have default
 * constructors, so they auto-resolve and are intentionally NOT bound here.
 * TransactionManager and DomainEventBus (Core\Shared kernel) are bound globally
 * elsewhere. Registered in bootstrap/providers.php.
 */
final class SubscriptionsServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        PlanRepository::class => EloquentPlanRepository::class,
        PlanCatalogReader::class => EloquentPlanRepository::class,
        SubscriptionRepository::class => EloquentSubscriptionRepository::class,
        PaymentRepository::class => EloquentPaymentRepository::class,
        PaymentReader::class => EloquentPaymentRepository::class,
    ];

    public function boot(): void
    {
        $registry = $this->app->make(DomainEventClassRegistry::class);
        $registry->register(PaymentNotified::eventName(), PaymentNotified::class);
        $registry->register(PaymentConfirmed::eventName(), PaymentConfirmed::class);
        $registry->register(PaymentRejected::eventName(), PaymentRejected::class);
        $registry->register(PaymentWasNotifiedForSubscription::eventName(), PaymentWasNotifiedForSubscription::class);
        $registry->register(SubscriptionWasUpgraded::eventName(), SubscriptionWasUpgraded::class);
        $registry->register(SubscriptionReturnedToActive::eventName(), SubscriptionReturnedToActive::class);
    }
}
