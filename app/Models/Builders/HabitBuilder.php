<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;

class HabitBuilder extends Builder
{
    public function active(): self
    {
        return $this->where('is_active', true);
    }

    public function forUser(int $userId): self
    {
        return $this->where('user_id', $userId);
    }
}