<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Domain;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

interface UserRepository
{
    /**
     * Persiste el User (insert si es nuevo, update si ya tiene id).
     * Si es nuevo, asigna UserId vía $user->assignId(...) y luego dispara
     * $user->recordRegisteredAfterAssign() antes de publicar los eventos.
     */
    public function save(User $user): void;

    /** Devuelve el User por id, incluyendo soft-deleted; null si no existe. */
    public function find(UserId $id): ?User;

    /** Igual que find pero filtra deleted_at IS NULL y is_active=true. */
    public function findActive(UserId $id): ?User;

    /** Busca por email; null si no existe (incluyendo soft-deleted). */
    public function findByEmail(EmailAddress $email): ?User;

    /** Sólo activos y no soft-deleted. Usado por el flujo de login. */
    public function findActiveByEmail(EmailAddress $email): ?User;

    /** Pre-check de unicidad. */
    public function emailExists(EmailAddress $email): bool;

    /** Soft-delete. Idempotente si ya estaba deleted. */
    public function delete(User $user): void;
}
