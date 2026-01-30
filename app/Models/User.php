<?php

namespace App\Models;

class User extends Base\User
{
    public function newEloquentBuilder($query): Builders\UserBuilder
    {
        return new Builders\UserBuilder($query);
    }

    public function getRedirectUrl(): string
    {
        return match ($this->is_admin) {
            true => route('backoffice.dashboard.index'),
            false => route('dashboard'),
        };
    }
}
