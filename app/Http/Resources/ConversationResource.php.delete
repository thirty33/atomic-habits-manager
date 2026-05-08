<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin Conversation
 */
class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'conversation_id' => $this->conversation_id,
            'title' => $this->title,
            'status' => $this->status,
            'last_message_at' => $this->last_message_at?->isoFormat('LL'),
            'last_message_preview' => $this->whenLoaded(
                'latestMessage',
                fn () => Str::limit($this->latestMessage?->body, 60)
            ),
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
            'delete_action' => [
                'url' => route('backoffice.atomic-ia.conversations.destroy', $this->conversation_id),
                'method' => 'delete',
            ],
        ];
    }
}
