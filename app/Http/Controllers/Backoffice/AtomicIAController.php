<?php

namespace App\Http\Controllers\Backoffice;

use App\Actions\SendMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\ViewModels\Backoffice\AtomicIA\GetAtomicIAViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AtomicIAController extends Controller
{
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

    public function store(SendMessageRequest $request): JsonResponse
    {
        $message = SendMessageAction::execute($request->validated());

        return response()->json([
            'message' => new MessageResource($message),
        ]);
    }
}
