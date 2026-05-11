<?php

declare(strict_types=1);

namespace Core\Shared\Infrastructure\Pipeline;

use Core\Shared\Application\Pipeline\PipelineRunner;
use Illuminate\Pipeline\Pipeline;

/**
 * Illuminate adapter for the PipelineRunner port. Delegates to Laravel's
 * pipeline implementation so Application code never has to import
 * Illuminate\Pipeline\Pipeline directly.
 */
final readonly class IlluminatePipelineRunner implements PipelineRunner
{
    public function __construct(
        private Pipeline $pipeline,
    ) {}

    public function run(mixed $passable, array $pipes): mixed
    {
        return $this->pipeline
            ->send($passable)
            ->through($pipes)
            ->thenReturn();
    }
}
