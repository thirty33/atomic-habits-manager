<?php

declare(strict_types=1);

namespace App\Providers;

use Core\BoundedContext\Calendar\Application\CalendarReader;
use Core\BoundedContext\Calendar\Infrastructure\Persistence\Eloquent\EloquentCalendarReader;
use Illuminate\Support\ServiceProvider;

final class CalendarServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        CalendarReader::class => EloquentCalendarReader::class,
    ];
}
