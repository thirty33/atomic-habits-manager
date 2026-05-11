<?php

declare(strict_types=1);

namespace Core\Shared\Application\Pipeline;

/**
 * Application port for chaining a series of pipe classes around a
 * shared passable. Use Cases that orchestrate multi-step flows pick
 * the pipes they need and call run(); the concrete implementation
 * (Laravel's pipeline, a hand-rolled chain, etc.) lives in
 * Infrastructure.
 */
interface PipelineRunner
{
    /**
     * Send the passable through the given pipe classes, in order, and
     * return whatever the last pipe yields.
     *
     * Each pipe class must be resolvable from the container and must
     * expose a `handle($passable, callable $next)` method (the same
     * contract Laravel's pipeline assumes).
     *
     * @param  list<class-string>  $pipes
     */
    public function run(mixed $passable, array $pipes): mixed;
}
