<?php

namespace App\Models\Base;

use App\Enums\MessageRole;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $table = 'messages';

    protected $primaryKey = 'message_id';

    protected $fillable = [
        'conversation_id',
        'role',
        'type',
        'body',
        'media_url',
        'status',
        'metadata',
    ];

    protected $casts = [
        'role' => MessageRole::class,
        'metadata' => 'array',
    ];
}
