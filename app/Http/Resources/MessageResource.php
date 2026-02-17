<?php

namespace App\Http\Resources;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Message
 */
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'message_id' => $this->message_id,
            'role' => $this->role,
            'type' => $this->type,
            'body' => $this->sanitizeBody($this->body),
            'media_url' => $this->media_url,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'created_at' => $this->created_at->format('H:i'),
        ];
    }

    private function sanitizeBody(?string $body): ?string
    {
        if ($body === null) {
            return null;
        }

        $body = strip_tags($body);
        $body = preg_replace('/!\[.*?\]\(.*?\)/', '', $body);

        return trim($body);
    }
}
