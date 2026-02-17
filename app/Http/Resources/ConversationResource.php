<?php

namespace App\Http\Resources;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'messages' => MessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
