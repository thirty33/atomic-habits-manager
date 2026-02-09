<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
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
}
