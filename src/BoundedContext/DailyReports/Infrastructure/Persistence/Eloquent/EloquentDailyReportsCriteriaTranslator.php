<?php

declare(strict_types=1);

namespace Core\BoundedContext\DailyReports\Infrastructure\Persistence\Eloquent;

use Core\BoundedContext\DailyReports\Application\Criteria\DailyReportsCriteria;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<\App\Models\DailyReport>
 */
final readonly class EloquentDailyReportsCriteriaTranslator
{
    /**
     * @param  Builder<\App\Models\DailyReport>  $query
     * @return Builder<\App\Models\DailyReport>
     */
    public function translate(Builder $query, DailyReportsCriteria $criteria): Builder
    {
        $query->where('user_id', $criteria->userId->value());

        if ($criteria->fromDate !== null) {
            $query->where('report_date', '>=', $criteria->fromDate->value());
        }

        if ($criteria->toDate !== null) {
            $query->where('report_date', '<=', $criteria->toDate->value());
        }

        if ($criteria->mood !== null) {
            $query->where('mood', $criteria->mood->value());
        }

        $query->orderBy($criteria->sortBy, $criteria->sortDir);

        return $query;
    }
}
