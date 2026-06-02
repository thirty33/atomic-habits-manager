<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Infrastructure\Persistence\Eloquent;

use App\Models\User as UserModel;
use Core\BoundedContext\Identity\Domain\User;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Illuminate\Support\Facades\DB;

final readonly class EloquentUserRepository implements UserRepository
{
    public function __construct(
        private UserModel $model,
        private DomainEventBus $bus,
    ) {}

    public function save(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $isNew = $user->isNew();

            $row = $isNew
                ? $this->model->newInstance()
                : $this->model->newQuery()->withTrashed()->find($user->userId()->value());

            $row->fill($this->toAttributes($user));

            // email_verified_at y remember_token no están en $fillable del modelo,
            // se asignan directo. El password ya viene hasheado por el agregado;
            // el cast 'hashed' detecta el formato bcrypt/argon y no rehashea.
            $row->email_verified_at = $user->emailVerifiedAt();
            $row->remember_token = $user->rememberToken()?->value();

            if ($isNew) {
                $row->setRawAttributes(array_merge($row->getAttributes(), [
                    'password' => $user->password()->value(),
                ]));
            } else {
                $row->password = $user->password()->value();
            }

            $row->save();

            if ($isNew) {
                $user->assignId(UserId::from((int) $row->getKey()));
                $user->recordRegisteredAfterAssign();
            }

            $this->bus->publish(...$user->pullDomainEvents());
        });
    }

    public function find(UserId $id): ?User
    {
        $row = $this->model->newQuery()->withTrashed()->find($id->value());

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function findActive(UserId $id): ?User
    {
        $row = $this->model->newQuery()
            ->where('user_id', $id->value())
            ->where('is_active', true)
            ->first();

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function findByEmail(EmailAddress $email): ?User
    {
        $row = $this->model->newQuery()->withTrashed()
            ->where('email', $email->value())
            ->first();

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function findActiveByEmail(EmailAddress $email): ?User
    {
        $row = $this->model->newQuery()
            ->where('email', $email->value())
            ->where('is_active', true)
            ->first();

        return $row !== null ? $this->toDomain($row) : null;
    }

    public function emailExists(EmailAddress $email): bool
    {
        return $this->model->newQuery()->withTrashed()
            ->where('email', $email->value())
            ->exists();
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $userId = $user->userId();

            if ($userId === null) {
                throw new \LogicException('Cannot delete a User without id.');
            }

            $this->model->newQuery()->where('user_id', $userId->value())->delete();
            $this->bus->publish(...$user->pullDomainEvents());
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function toAttributes(User $user): array
    {
        return [
            'name' => $user->name()->value(),
            'email' => $user->email()->value(),
            'is_active' => $user->isActive(),
            'is_admin' => $user->isAdmin(),
            'email_verified_at' => $user->emailVerifiedAt()?->format('Y-m-d H:i:s'),
            'remember_token' => $user->rememberToken()?->value(),
        ];
    }

    private function toDomain(UserModel $row): User
    {
        $attrs = $row->getAttributes();

        return User::fromPrimitives(
            userId: (int) $attrs['user_id'],
            name: (string) $attrs['name'],
            email: (string) $attrs['email'],
            hashedPassword: (string) $attrs['password'],
            isActive: (bool) $attrs['is_active'],
            isAdmin: (bool) $attrs['is_admin'],
            emailVerifiedAt: $this->nullable($attrs, 'email_verified_at'),
            rememberToken: $this->nullable($attrs, 'remember_token'),
            createdAt: $this->nullable($attrs, 'created_at'),
            updatedAt: $this->nullable($attrs, 'updated_at'),
            deletedAt: $this->nullable($attrs, 'deleted_at'),
        );
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function nullable(array $attrs, string $key): ?string
    {
        if (! array_key_exists($key, $attrs) || $attrs[$key] === null) {
            return null;
        }

        return (string) $attrs[$key];
    }
}
