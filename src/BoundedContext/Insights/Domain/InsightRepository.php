<?php

declare(strict_types=1);

namespace Core\BoundedContext\Insights\Domain;

/**
 * Write-side port for the Insight aggregate. Pure domain: no Illuminate/App
 * imports. Assigns an InsightId on first save.
 */
interface InsightRepository
{
    public function save(Insight $insight): void;
}
