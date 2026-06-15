<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardInsight extends Model
{
    protected $primaryKey = 'insight_id';

    protected $fillable = [
        'user_id',
        'message',
        'generated_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }
}
