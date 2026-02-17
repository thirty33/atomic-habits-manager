<?php

namespace App\Console\Commands;

use App\Enums\MessageRole;
use App\Events\MessageSent;
use App\Models\Conversation;
use Illuminate\Console\Command;

class AtomicIAReplyCommand extends Command
{
    protected $signature = 'atomic-ia:reply {conversation_id} {message}';

    protected $description = 'Simula una respuesta de la IA en una conversación';

    public function handle(): int
    {
        $conversation = Conversation::findOrFail($this->argument('conversation_id'));

        $message = $conversation->messages()->create([
            'role' => MessageRole::Assistant,
            'type' => 'text',
            'body' => $this->argument('message'),
            'status' => 'sent',
        ]);

        $conversation->update(['last_message_at' => now()]);

        MessageSent::dispatch($conversation, $message);

        $this->info("Mensaje broadcasteado en canal: conversation.{$conversation->conversation_id}");

        return self::SUCCESS;
    }
}
