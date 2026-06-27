<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Application\Responses;

/**
 * Paginated listing of users for the backoffice Users module. Holds plain
 * {@see UserResponse} rows plus pagination meta; the ViewModel maps it onto the
 * datatable's LengthAwarePaginator contract.
 */
final readonly class UsersPaginatedResponse
{
    /**
     * @param  list<UserResponse>  $data
     * @param  array{current_page: int, last_page: int, per_page: int, total: int}  $meta
     */
    public function __construct(
        public array $data,
        public array $meta,
    ) {}
}
