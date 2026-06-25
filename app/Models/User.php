<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Base\User
{
    public function newEloquentBuilder($query): Builders\UserBuilder
    {
        return new Builders\UserBuilder($query);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role_id',
        );
    }

    public function getRedirectUrl(): string
    {
        return match ($this->is_admin) {
            true => route('backoffice.dashboard.index'),
            false => route('dashboard'),
        };
    }

    /** Whether this is still an unclaimed guest auto-user (D1), derived from the explicit `claimed_at` column (null = guest). */
    public function isGuest(): bool
    {
        return $this->claimed_at === null;
    }
}
