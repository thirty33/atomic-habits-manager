<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\ViewModels\Backoffice\Calendar\GetCalendarViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        return view('backoffice.calendar.index', [
            'json_url' => route('backoffice.calendar.json'),
        ]);
    }

    public function json(GetCalendarViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->toArray());
    }
}
