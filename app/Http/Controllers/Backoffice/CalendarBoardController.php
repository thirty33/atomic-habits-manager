<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Resources\CalendarBlockResource;
use App\ViewModels\Backoffice\Calendar\CalendarBoardViewModel;
use Core\BoundedContext\Calendar\Application\Actions\GetCalendarBlocksInRange;
use Core\BoundedContext\Calendar\Domain\ValueObjects\CalendarPeriod;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\View\View;

class CalendarBoardController extends Controller
{
    public function index(): View
    {
        return view('backoffice.calendar.board', [
            'json_url' => route('backoffice.calendar.json'),
            'blocks_url' => route('backoffice.calendar.blocks'),
        ]);
    }

    public function json(CalendarBoardViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->toArray());
    }

    public function blocks(Request $request, GetCalendarBlocksInRange $useCase): AnonymousResourceCollection
    {
        $request->validate([
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $snapshots = $useCase(
            UserId::from((int) $request->user()->user_id),
            CalendarPeriod::of(
                substr((string) $request->input('start'), 0, 10),
                substr((string) $request->input('end'), 0, 10),
            ),
        );

        return CalendarBlockResource::collection($snapshots);
    }
}
