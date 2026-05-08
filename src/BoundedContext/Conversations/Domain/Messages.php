<?php

declare(strict_types=1);

namespace Core\BoundedContext\Conversations\Domain;

use Core\Shared\Domain\Collection;

/**
 * @extends Collection
 */
final class Messages extends Collection
{
    protected function type(): string
    {
        return Message::class;
    }

    /**
     * @return list<Message>
     */
    public function items(): array
    {
        /** @var list<Message> */
        return $this->items;
    }

    /**
     * @template T
     *
     * @param  callable(Message): T  $fn
     * @return list<T>
     */
    public function map(callable $fn): array
    {
        return array_map($fn, $this->items);
    }
}
