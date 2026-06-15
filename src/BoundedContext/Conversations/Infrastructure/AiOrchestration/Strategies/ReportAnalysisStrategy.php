<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Strategies;

use Core\BoundedContext\Conversations\Infrastructure\AiOrchestration\Contracts\ListableResource;
use Core\BoundedContext\DailyReports\Application\Actions\BuildReportAnalysis;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * Read-only AI resource that studies the user's daily reports and surfaces the
 * facts an agent needs to propose improvements. The facts come from the shared
 * BuildReportAnalysis use case; this strategy only frames them for the chat
 * agent (the suggestion itself is the model's job).
 */
final class ReportAnalysisStrategy implements ListableResource
{
    public function __construct(private readonly BuildReportAnalysis $analysis) {}

    public function resourceName(): string
    {
        return 'report_analysis';
    }

    public function resourceDescription(): string
    {
        return 'Análisis de los reportes diarios del usuario: adherencia media, racha, tasa de reporte, cumplimiento por hábito en las últimas dos semanas y la reflexión más reciente. Úsalo para fundamentar sugerencias de mejora.';
    }

    public function list(int $userId, ?int $parentId = null): string
    {
        return ($this->analysis)(UserId::from($userId))
            ."\n\nUsa estos datos para ofrecer 1-2 sugerencias concretas y accionables (qué hábito reforzar, reprogramar o simplificar), en tono breve y motivador.";
    }
}
