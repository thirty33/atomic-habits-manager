<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Resources\HabitOccurrenceResource;
use App\Repositories\OccurrenceRepository;
use App\ViewModels\Backoffice\Calendar\GetCalendarViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(): View
    {
        return view('backoffice.calendar.index', [
            'json_url' => route('backoffice.calendar.json'),
            'occurrences_url' => route('backoffice.calendar.occurrences'),
        ]);
    }

    public function json(GetCalendarViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->toArray());
    }

    public function occurrences(Request $request, OccurrenceRepository $repository): AnonymousResourceCollection
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $start = CarbonImmutable::parse($request->input('start'));
        $end = CarbonImmutable::parse($request->input('end'));

        $occurrences = $repository->getForUserInRange(auth()->id(), $start, $end);

        return HabitOccurrenceResource::collection($occurrences);
    }
}
