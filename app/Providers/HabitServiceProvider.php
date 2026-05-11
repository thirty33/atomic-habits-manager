<?php

declare(strict_types=1);

namespace App\Providers;

use Core\BoundedContext\DailyReports\Domain\DailyReportRepository;
use Core\BoundedContext\DailyReports\Infrastructure\Persistence\Eloquent\EloquentDailyReportRepository;
use Core\BoundedContext\HabitOccurrences\Application\HabitOccurrenceReader;
use Core\BoundedContext\HabitOccurrences\Domain\HabitOccurrenceRepository;
use Core\BoundedContext\HabitOccurrences\Infrastructure\Persistence\Eloquent\EloquentHabitOccurrenceRepository;
use Core\BoundedContext\Habits\Domain\HabitRepository;
use Core\BoundedContext\Habits\Infrastructure\Persistence\Eloquent\EloquentHabitRepository;
use Core\BoundedContext\HabitSchedules\Application\HabitScheduleReader;
use Core\BoundedContext\HabitSchedules\Domain\HabitScheduleRepository;
use Core\BoundedContext\HabitSchedules\Infrastructure\Persistence\Eloquent\EloquentHabitScheduleRepository;
use Core\Shared\Domain\Bus\DomainEventBus;
use Core\Shared\Infrastructure\Events\Bus\DomainEventSubscriptions;
use Core\Shared\Infrastructure\Events\Bus\InMemorySyncDomainEventBus;
use Core\Shared\Infrastructure\Events\Outbox\DomainEventClassRegistry;
use Illuminate\Support\ServiceProvider;

/**
 * Bindings de las capas Domain (Habits + HabitSchedules) a sus
 * implementaciones concretas Eloquent.
 *
 * Cuando el BC HabitSchedules se migre a DDD pleno (entidad + Use Cases
 * de write), su binding se extraerá a un HabitScheduleServiceProvider
 * propio. Por ahora viven juntos por economía.
 */
final class HabitServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        HabitRepository::class => EloquentHabitRepository::class,
        HabitScheduleRepository::class => EloquentHabitScheduleRepository::class,
        HabitScheduleReader::class => EloquentHabitScheduleRepository::class,
        HabitOccurrenceRepository::class => EloquentHabitOccurrenceRepository::class,
        HabitOccurrenceReader::class => EloquentHabitOccurrenceRepository::class,
        DailyReportRepository::class => EloquentDailyReportRepository::class,
    ];

    public function register(): void
    {
        $this->app->singleton(DomainEventBus::class, function ($app) {
            if ($app->environment('testing')) {
                return $app->make(InMemorySyncDomainEventBus::class);
            }

            return $app->make(\Core\Shared\Infrastructure\Events\Bus\OutboxDomainEventBus::class);
        });

        $registry = $this->app->make(DomainEventClassRegistry::class);
        $registry->register(
            \Core\BoundedContext\Habits\Domain\Events\HabitWasCreated::eventName(),
            \Core\BoundedContext\Habits\Domain\Events\HabitWasCreated::class,
        );
        $registry->register(
            \Core\BoundedContext\Habits\Domain\Events\HabitWasUpdated::eventName(),
            \Core\BoundedContext\Habits\Domain\Events\HabitWasUpdated::class,
        );
        $registry->register(
            \Core\BoundedContext\Habits\Domain\Events\HabitWasSoftDeleted::eventName(),
            \Core\BoundedContext\Habits\Domain\Events\HabitWasSoftDeleted::class,
        );
        $registry->register(
            \Core\BoundedContext\Habits\Domain\Events\HabitWasRestored::eventName(),
            \Core\BoundedContext\Habits\Domain\Events\HabitWasRestored::class,
        );
        $registry->register(
            \Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasCreated::eventName(),
            \Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasCreated::class,
        );
        $registry->register(
            \Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasUpdated::eventName(),
            \Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasUpdated::class,
        );
        $registry->register(
            \Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasDeleted::eventName(),
            \Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasDeleted::class,
        );

        $subscriptions = $this->app->make(DomainEventSubscriptions::class);

        $subscriptions->register(
            \Core\BoundedContext\Habits\Domain\Events\HabitWasUpdated::class,
            \Core\BoundedContext\HabitOccurrences\Application\EventHandlers\RebuildOccurrencesWhenHabitWasUpdated::class,
        );
        $subscriptions->register(
            \Core\BoundedContext\Habits\Domain\Events\HabitWasSoftDeleted::class,
            \Core\BoundedContext\HabitOccurrences\Application\EventHandlers\CleanupOccurrencesWhenHabitWasSoftDeleted::class,
        );
        $subscriptions->register(
            \Core\BoundedContext\Habits\Domain\Events\HabitWasRestored::class,
            \Core\BoundedContext\HabitOccurrences\Application\EventHandlers\RebuildOccurrencesWhenHabitWasRestored::class,
        );
        $subscriptions->register(
            \Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasCreated::class,
            \Core\BoundedContext\HabitOccurrences\Application\EventHandlers\RebuildOccurrencesWhenScheduleWasCreated::class,
        );
        $subscriptions->register(
            \Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasUpdated::class,
            \Core\BoundedContext\HabitOccurrences\Application\EventHandlers\RebuildOccurrencesWhenScheduleWasUpdated::class,
        );
        $subscriptions->register(
            \Core\BoundedContext\HabitSchedules\Domain\Events\HabitScheduleWasDeleted::class,
            \Core\BoundedContext\HabitOccurrences\Application\EventHandlers\RebuildOccurrencesWhenScheduleWasDeleted::class,
        );
    }
}
