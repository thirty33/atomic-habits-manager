<?php

declare(strict_types=1);

namespace Core\BoundedContext\Insights\Application;

/**
 * Port for turning a report-analysis context into a single, user-facing
 * suggestion. The concrete adapter (Infrastructure) calls the LLM; the
 * Application layer stays provider-agnostic.
 */
interface InsightTextGenerator
{
    public function generate(string $analysis): string;
}
