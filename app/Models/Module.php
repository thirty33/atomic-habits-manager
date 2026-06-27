<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Base\Module
{
    public function permissions(): HasMany
    {
        return $this->hasMany(Permission::class, 'module_id', 'module_id');
    }
}
