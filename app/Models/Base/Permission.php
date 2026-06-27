<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';

    protected $primaryKey = 'permission_id';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'module_id',
        'code',
    ];
}
