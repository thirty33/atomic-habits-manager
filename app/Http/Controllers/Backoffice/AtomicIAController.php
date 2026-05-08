<?php

namespace App\Http\Controllers\Backoffice;

use App\Actions\SendMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Repositories\ConversationRepository;
use App\ViewModels\Backoffice\AtomicIA\GetAtomicIAViewModel;
use Core\BoundedContext\Conversations\Application\Actions\DeleteConversation;
use Core\BoundedContext\Conversations\Application\Actions\StartConversation;
use Core\BoundedContext\Conversations\Application\DTOs\DeleteConversationData;
use Core\BoundedContext\Conversations\Domain\Exceptions\ConversationNotFound;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AtomicIAController extends Controller
{
    public static function middleware(): array
    {
        return [
            new \Illuminate\Routing\Controllers\Middleware('throttle:atomic-ia', only: ['store', 'newConversation']),
        ];
    }

    public function index(): View
    {
        return view('backoffice.atomic-ia.index', [
            'json_url' => route('backoffice.atomic-ia.json'),
        ]);
    }

    public function json(GetAtomicIAViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->toArray());
    }

    public function store(SendMessageRequest $request, ConversationRepository $repository): JsonResponse
    {
        $conversation = $repository->getByIdAndUser(
            (int) $request->query('conversation_id'),
            auth()->id()
        );

        abort_if(is_null($conversation), 404);

        $message = SendMessageAction::execute($conversation, $request->validated());

        return response()->json([
            'message' => new MessageResource($message),
        ]);
    }

    public function newConversation(StartConversation $startConversation): JsonResponse
    {
        $response = $startConversation(UserId::from((int) auth()->id()));

        return response()->json([
            'conversation' => $response->toArray(),
            'store_url' => route('backoffice.atomic-ia.store', ['conversation_id' => $response->conversationId]),
        ]);
    }

    public function destroyConversation(int $id, DeleteConversation $deleteConversation): JsonResponse
    {
        try {
            $deleteConversation(new DeleteConversationData(
                conversationId: $id,
                userId: (int) auth()->id(),
            ));
        } catch (ConversationNotFound) {
            abort(404);
        }

        return response()->json(['message' => 'Conversación eliminada.']);
    }
}
