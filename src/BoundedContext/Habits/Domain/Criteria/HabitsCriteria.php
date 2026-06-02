<?php

declare(strict_types=1);

namespace Core\BoundedContext\Habits\Domain\Criteria;

use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\DesireType;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\HabitNature;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * Criterio para listar Habits. Es un Value Object inmutable: cada `with*`
 * devuelve una nueva instancia.
 *
 * Modela la pregunta "¿qué habits quiero ver?" en lenguaje de dominio:
 *   - userId: dueño (siempre obligatorio)
 *   - search: búsqueda libre por name/description
 *   - nature: filtro por HabitNature (build/break)
 *   - desireType: filtro por DesireType (need/want/neutral)
 *   - isActive: filtro por activo / inactivo
 *   - sort: orden (HabitsSort)
 *   - page / perPage: paginación
 *
 * El adapter (HabitsCriteriaTranslator) traduce este VO a Eloquent.
 */
final readonly class HabitsCriteria
{
    public const DEFAULT_PER_PAGE = 10;

    public const MAX_PER_PAGE = 100;

    private function __construct(
        public UserId $userId,
        public ?string $search,
        public ?HabitNature $nature,
        public ?DesireType $desireType,
        public ?bool $isActive,
        public HabitsSort $sort,
        public int $page,
        public int $perPage,
    ) {
        if ($page < 1) {
            throw new \InvalidArgumentException(sprintf('page must be >= 1, got %d', $page));
        }

        if ($perPage < 1 || $perPage > self::MAX_PER_PAGE) {
            throw new \InvalidArgumentException(sprintf(
                'perPage must be between 1 and %d, got %d',
                self::MAX_PER_PAGE,
                $perPage
            ));
        }
    }

    public static function forUser(UserId $userId): self
    {
        return new self(
            userId: $userId,
            search: null,
            nature: null,
            desireType: null,
            isActive: null,
            sort: HabitsSort::default(),
            page: 1,
            perPage: self::DEFAULT_PER_PAGE,
        );
    }

    public function withSearch(?string $search): self
    {
        $normalized = $search !== null ? trim($search) : null;
        $normalized = $normalized === '' ? null : $normalized;

        return new self(
            $this->userId, $normalized, $this->nature, $this->desireType,
            $this->isActive, $this->sort, $this->page, $this->perPage,
        );
    }

    public function withNature(?HabitNature $nature): self
    {
        return new self(
            $this->userId, $this->search, $nature, $this->desireType,
            $this->isActive, $this->sort, $this->page, $this->perPage,
        );
    }

    public function withDesireType(?DesireType $desireType): self
    {
        return new self(
            $this->userId, $this->search, $this->nature, $desireType,
            $this->isActive, $this->sort, $this->page, $this->perPage,
        );
    }

    public function withIsActive(?bool $isActive): self
    {
        return new self(
            $this->userId, $this->search, $this->nature, $this->desireType,
            $isActive, $this->sort, $this->page, $this->perPage,
        );
    }

    public function withSort(HabitsSort $sort): self
    {
        return new self(
            $this->userId, $this->search, $this->nature, $this->desireType,
            $this->isActive, $sort, $this->page, $this->perPage,
        );
    }

    public function withPage(int $page): self
    {
        return new self(
            $this->userId, $this->search, $this->nature, $this->desireType,
            $this->isActive, $this->sort, $page, $this->perPage,
        );
    }

    public function withPerPage(int $perPage): self
    {
        return new self(
            $this->userId, $this->search, $this->nature, $this->desireType,
            $this->isActive, $this->sort, $this->page, $perPage,
        );
    }
}
