<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $primaryKey = 'payment_id';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'plan',
        'amount',
        'currency',
        'payer_binance_email',
        'tx_reference',
        'status',
        'notified_at',
        'confirmed_at',
        'confirmed_by',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'notified_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];
}
