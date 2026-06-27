<?php

declare(strict_types=1);

namespace Core\BoundedContext\Identity\Infrastructure\Persistence\Eloquent;

use App\Models\User as UserModel;
use Core\BoundedContext\Identity\Application\DTOs\ListUsersData;
use Core\BoundedContext\Identity\Application\Responses\UserResponse;
use Core\BoundedContext\Identity\Application\Responses\UsersPaginatedResponse;
use Core\BoundedContext\Identity\Application\UserReader;
use Core\BoundedContext\Identity\Domain\User;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\EmailAddress;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\Shared\Domain\Bus\DomainEventBus;
use Illuminate\Support\Facades\DB;

final readonly class EloquentUserRepository implements UserReader, UserRepository
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

            // email_verified_at, claimed_at and remember_token are not in the
            // model's $fillable, so they are assigned directly. The password is
            // already hashed by the aggregate; the 'hashed' cast detects the
            // bcrypt/argon format and does not rehash it.
            $row->email_verified_at = $user->emailVerifiedAt();
            $row->claimed_at = $user->claimedAt();
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

    public function paginate(ListUsersData $data): UsersPaginatedResponse
    {
        $query = $this->model->newQuery();

        if ($data->search !== null) {
            $query->where(function ($q) use ($data): void {
                $q->where('name', 'like', '%'.$data->search.'%')
                    ->orWhere('email', 'like', '%'.$data->search.'%');
            });
        }

        if ($data->isActive !== null) {
            $query->where('is_active', $data->isActive);
        }

        $sortField = in_array($data->sortField, ['name', 'email', 'is_active', 'created_at'], true)
            ? $data->sortField
            : 'created_at';
        $sortDirection = strtolower($data->sortDirection) === 'asc' ? 'asc' : 'desc';

        $paginator = $query->orderBy($sortField, $sortDirection)
            ->paginate(perPage: $data->perPage, page: $data->page);

        $rows = array_map(
            fn (UserModel $row): UserResponse => UserResponse::fromUser($this->toDomain($row)),
            $paginator->items(),
        );

        return new UsersPaginatedResponse(
            data: $rows,
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        );
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
            'claimed_at' => $user->claimedAt()?->format('Y-m-d H:i:s'),
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
            claimedAt: $this->nullable($attrs, 'claimed_at'),
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
