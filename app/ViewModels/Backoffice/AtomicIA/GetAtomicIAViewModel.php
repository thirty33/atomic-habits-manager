<?php

namespace App\ViewModels\Backoffice\AtomicIA;

use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\ViewModels\FilterService;
use App\ViewModels\ViewModel;
use Illuminate\Pipeline\Pipeline;

class GetAtomicIAViewModel extends ViewModel
{
    public function __construct(
        private readonly Pipeline $pipeline,
        private readonly FilterService $filterService,
    ) {}

    public function pageTitle(): string
    {
        return __('Atomic IA');
    }

    public function storeUrl(): string
    {
        return route('backoffice.atomic-ia.store');
    }

    protected function conversationFilters(): array
    {
        return [];
    }

    public function conversation(): ?ConversationResource
    {
        $query = $this->pipeline
            ->send(Conversation::query()->where('user_id', auth()->id())->with(['messages' => fn ($q) => $q->orderBy('created_at')]))
            ->through(
                collect($this->conversationFilters())
                    ->values()
                    ->all()
            )->thenReturn();

        $conversation = $query->latest('last_message_at')->first();

        return $conversation ? new ConversationResource($conversation) : null;
    }
}
