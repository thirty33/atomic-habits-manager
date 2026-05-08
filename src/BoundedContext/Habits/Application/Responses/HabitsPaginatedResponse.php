<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Application\Responses;

use Core\BoundedContext\Habits\Domain\Criteria\HabitsPage;

/**
 * Respuesta paginada con shape `{ data: [...], meta: {...} }`. El
 * controller la serializa directo: `response()->json($response->toArray())`.
 */
final readonly class HabitsPaginatedResponse
{
    /**
     * @param  list<HabitResponse>  $data
     * @param  array{
     *     current_page: int,
     *     from: int,
     *     to: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int,
     *     has_next_page: bool,
     *     has_previous_page: bool,
     * }  $meta
     */
    public function __construct(
        public array $data,
        public array $meta,
    ) {}

    public static function fromPage(HabitsPage $page): self
    {
        $data = $page->items->map(static fn ($habit) => HabitResponse::fromHabit($habit));

        return new self(
            data: $data,
            meta: [
                'current_page' => $page->page,
                'from' => $page->from(),
                'to' => $page->to(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage,
                'total' => $page->total,
                'has_next_page' => $page->hasNextPage(),
                'has_previous_page' => $page->hasPreviousPage(),
            ],
        );
    }

    /**
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (HabitResponse $r) => $r->toArray(), $this->data),
            'meta' => $this->meta,
        ];
    }
}
