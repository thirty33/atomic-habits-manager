<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Resources\HabitOccurrenceResource;
use App\ViewModels\Backoffice\Calendar\GetCalendarViewModel;
use Core\BoundedContext\HabitOccurrences\Application\Actions\GetOccurrencesInRange;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Habits\Domain\ValueObjects\Concretes\UserId;
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

    public function occurrences(Request $request, GetOccurrencesInRange $useCase): AnonymousResourceCollection
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after:start'],
        ]);

        $snapshots = $useCase(
            UserId::from((int) $request->user()->user_id),
            OccurrenceDate::fromString(substr((string) $request->input('start'), 0, 10)),
            OccurrenceDate::fromString(substr((string) $request->input('end'), 0, 10)),
        );

        return HabitOccurrenceResource::collection($snapshots);
    }
}
