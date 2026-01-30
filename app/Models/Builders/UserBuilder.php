<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

class UserBuilder extends Builder
{
    public function active(): self
    {
        return $this->where('is_active', true);
    }

    public function admins(): self
    {
        return $this->where('is_admin', true);
    }
}
