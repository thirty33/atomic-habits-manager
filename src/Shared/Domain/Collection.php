<?php

declare(strict_types=1);

namespace Core\Shared\Domain;

/**
 * Base abstracta para colecciones tipadas del dominio.
 *
 * Una colección concreta debe declarar el tipo de sus items vía `type()`.
 * El constructor valida en runtime que cada item sea instancia del tipo
 * declarado, así si alguien mete un Habit dentro de DailyReports el código
 * falla rápido y con mensaje claro.
 */
abstract class Collection implements \Countable, \IteratorAggregate
{
    /** @var list<object> */
    protected array $items;

    /**
     * @param  list<object>  $items
     */
    public function __construct(array $items)
    {
        foreach ($items as $item) {
            if (! $item instanceof ($this->type())) {
                throw new \InvalidArgumentException(sprintf(
                    '%s only accepts items of type %s, got %s.',
                    static::class,
                    $this->type(),
                    is_object($item) ? $item::class : gettype($item)
                ));
            }
        }

        $this->items = array_values($items);
    }

    /**
     * Class-string del tipo de los items que la colección acepta.
     *
     * @return class-string
     */
    abstract protected function type(): string;

    /**
     * @return list<object>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}
