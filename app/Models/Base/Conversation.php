<?php

namespace App\Models\Base;

use App\Enums\ConversationStatus;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $table = 'conversations';

    protected $primaryKey = 'conversation_id';

    protected $fillable = [
        'user_id',
        'title',
        'status',
        'last_message_at',
    ];

    protected $casts = [
        'status' => ConversationStatus::class,
        'last_message_at' => 'datetime',
    ];
}
