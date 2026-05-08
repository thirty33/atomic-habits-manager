<?php

namespace App\Actions\Messages;

use App\Actions\Contracts\UpdateAction;
use App\Models\Message;

final class UpdateMessageStatusAction implements UpdateAction
{
    public static function execute(int $id, array $data = []): void
    {
        $message = Message::findOrFail($id);

        $message->update([
            'status' => data_get($data, 'status'),
            'metadata' => [
                ...(array) $message->metadata,
                'moderation' => data_get($data, 'moderation'),
            ],
        ]);
    }
}
