<?php

namespace App\ViewModels\Backoffice\AtomicIA;

use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Repositories\ConversationRepository;
use App\Services\ViewModels\FilterService;
use App\ViewModels\ViewModel;
use Illuminate\Pipeline\Pipeline;

class GetAtomicIAViewModel extends ViewModel
{
    public function __construct(
        private readonly Pipeline $pipeline,
        private readonly FilterService $filterService,
        private readonly ConversationRepository $conversationRepository,
    ) {}

    public function pageTitle(): string
    {
        return __('Atomic IA');
    }

    public function storeUrl(): string
    {
        $conversation = $this->resolveConversation();

        return $conversation
            ? route('backoffice.atomic-ia.store', ['conversation_id' => $conversation->conversation_id])
            : '';
    }

    public function newConversationUrl(): string
    {
        return route('backoffice.atomic-ia.new-conversation');
    }

    public function conversation(): ?ConversationResource
    {
        $conversation = $this->resolveConversation();

        return $conversation ? new ConversationResource($conversation) : null;
    }

    public function conversations(): array
    {
        return ConversationResource::collection(
            $this->conversationRepository->getAllByUserWithLatestMessage(auth()->id())
        )->resolve();
    }

    private function resolveConversation(): ?Conversation
    {
        $filters = $this->filterService->generateNormalFilter(key: 'conversation_id');
        $conversationId = $filters['conversation_id'];

        if ($conversationId) {
            return $this->conversationRepository->getByIdAndUser((int) $conversationId, auth()->id());
        }

        return $this->conversationRepository->getLatestActiveByUserWithMessages(auth()->id());
    }
}
