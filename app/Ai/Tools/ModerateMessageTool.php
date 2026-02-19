<?php

namespace App\Ai\Tools;

use App\Actions\Conversations\BanConversationAction;
use App\Actions\Messages\UpdateMessageStatusAction;
use App\Enums\MessageStatus;
use App\Models\Message;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class ModerateMessageTool implements Tool
{
    public function __construct(private Message $message) {}

    public function description(): Stringable|string
    {
        return 'Registra la decisión de moderación del mensaje. '
            .'Úsala siempre al finalizar tu evaluación.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'approved' => $schema->boolean()
                ->description('true si el mensaje es seguro, false si contiene una amenaza.')
                ->required(),
            'reason' => $schema->string()
                ->description('Razón del rechazo en español. Solo requerida si approved es false.'),
        ];
    }

    public function handle(Request $request): Stringable|string
    {
        $approved = (bool) $request['approved'];

        UpdateMessageStatusAction::execute($this->message->message_id, [
            'status' => $approved ? MessageStatus::Approved : MessageStatus::Banned,
            'moderation' => [
                'approved' => $approved,
                'reason' => $request['reason'] ?? null,
            ],
        ]);

        if (! $approved) {
            BanConversationAction::execute($this->message->conversation->conversation_id);
        }

        return $approved ? 'Mensaje aprobado.' : 'Mensaje baneado y conversación cerrada.';
    }
}
