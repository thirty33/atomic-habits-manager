<?php

declare(strict_types=1);

namespace App\Providers;

use Core\BoundedContext\Insights\Application\InsightReader;
use Core\BoundedContext\Insights\Application\InsightTextGenerator;
use Core\BoundedContext\Insights\Domain\InsightRepository;
use Core\BoundedContext\Insights\Infrastructure\Ai\LaravelAiInsightTextGenerator;
use Core\BoundedContext\Insights\Infrastructure\Persistence\Eloquent\EloquentInsightRepository;
use Illuminate\Support\ServiceProvider;

/**
 * Bindings for the Insights bounded context (dashboard suggestions).
 */
final class InsightServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        InsightRepository::class => EloquentInsightRepository::class,
        InsightReader::class => EloquentInsightRepository::class,
    ];

    public function register(): void
    {
        $this->app->singleton(
            InsightTextGenerator::class,
            fn ($app): LaravelAiInsightTextGenerator => new LaravelAiInsightTextGenerator(
                provider: (string) config('ai.default'),
                model: (string) config('ai.model'),
            ),
        );
    }
}
