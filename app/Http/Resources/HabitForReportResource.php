<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Core\BoundedContext\Habits\Application\ReadModels\HabitSnapshot;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HabitSnapshot
 */
final class HabitForReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var HabitSnapshot $snap */
        $snap = $this->resource;

        return [
            'habit_id' => $snap->habitId,
            'name' => $snap->name,
            'color' => $snap->color,
        ];
    }
}
