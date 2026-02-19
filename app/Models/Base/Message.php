<?php

namespace App\Models\Base;

use App\Enums\MessageRole;
use App\Enums\MessageStatus;
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

    protected function casts(): array
    {
        return [
            'role' => MessageRole::class,
            'status' => MessageStatus::class,
            'metadata' => 'array',
        ];
    }
}
