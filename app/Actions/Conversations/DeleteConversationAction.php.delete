<?php

namespace App\Actions\Conversations;

use App\Actions\Contracts\DeleteAction;
use App\Models\Conversation;

final class DeleteConversationAction implements DeleteAction
{
    public static function execute(int $id): void
    {
        Conversation::where('user_id', auth()->id())->findOrFail($id)->delete();
    }
}
