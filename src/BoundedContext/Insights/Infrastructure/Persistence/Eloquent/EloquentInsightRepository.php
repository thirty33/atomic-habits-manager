<?php

declare(strict_types=1);

namespace Core\BoundedContext\Insights\Infrastructure\Persistence\Eloquent;

use App\Models\DashboardInsight as InsightModel;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Insights\Application\InsightReader;
use Core\BoundedContext\Insights\Application\ReadModels\InsightSnapshot;
use Core\BoundedContext\Insights\Domain\Insight;
use Core\BoundedContext\Insights\Domain\InsightRepository;
use Core\BoundedContext\Insights\Domain\ValueObjects\InsightId;

final readonly class EloquentInsightRepository implements InsightReader, InsightRepository
{
    public function __construct(private InsightModel $model) {}

    public function save(Insight $insight): void
    {
        $row = $this->model->newInstance();
        $row->fill([
            'user_id' => $insight->userId()->value(),
            'message' => $insight->message(),
            'generated_at' => $insight->generatedAt()->format('Y-m-d H:i:s'),
        ]);
        $row->save();

        $insight->assignId(InsightId::from((int) $row->getKey()));
    }

    public function latestForUser(UserId $userId): ?InsightSnapshot
    {
        $row = $this->model->newQuery()
            ->where('user_id', $userId->value())
            ->orderByDesc('generated_at')
            ->orderByDesc('insight_id')
            ->first();

        if ($row === null) {
            return null;
        }

        $attrs = $row->getAttributes();

        return new InsightSnapshot(
            message: (string) $attrs['message'],
            generatedAt: (string) $attrs['generated_at'],
        );
    }
}
