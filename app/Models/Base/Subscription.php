<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $table = 'subscriptions';

    protected $primaryKey = 'subscription_id';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'plan_tier',
        'status',
    ];
}
