<?php

namespace App\Actions;

use App\Enums\MessageRole;
use App\Enums\MessageStatus;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;

final class CreateFallbackMessageAction
{
    public static function execute(Conversation $conversation): Message
    {
        $message = $conversation->messages()->create([
            'role' => MessageRole::Assistant,
            'type' => 'text',
            'body' => 'Lo siento, no puedo continuar esta conversación. '
                .'Ha sido cerrada por motivos de seguridad.',
            'status' => MessageStatus::Approved,
        ]);

        MessageSent::dispatch($conversation, $message);

        return $message;
    }
}
