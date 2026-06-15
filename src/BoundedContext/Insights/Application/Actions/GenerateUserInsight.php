<?php

declare(strict_types=1);

namespace Core\BoundedContext\Insights\Application\Actions;

use Core\BoundedContext\DailyReports\Application\Actions\BuildReportAnalysis;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Insights\Application\InsightTextGenerator;
use Core\BoundedContext\Insights\Domain\Insight;
use Core\BoundedContext\Insights\Domain\InsightRepository;
use DateTimeImmutable;

/**
 * Generates and persists one dashboard insight for a user: gathers the report
 * analysis facts, asks the LLM for a single suggestion, and stores it. Meant to
 * be driven by a daily job, never per web request.
 */
final readonly class GenerateUserInsight
{
    public function __construct(
        private BuildReportAnalysis $analysis,
        private InsightTextGenerator $generator,
        private InsightRepository $insights,
    ) {}

    public function __invoke(UserId $userId, ?string $today = null): void
    {
        $analysis = ($this->analysis)($userId, $today);
        $message = $this->generator->generate($analysis);

        $this->insights->save(
            Insight::generate($userId, $message, new DateTimeImmutable),
        );
    }
}
