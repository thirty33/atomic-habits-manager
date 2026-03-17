<?php

namespace App\Http\Controllers\Backoffice;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DailyReportRequest;
use App\Http\Requests\SaveReportEntriesRequest;
use App\Http\Requests\UpdateDailyReportRequest;
use App\Http\Resources\DailyReportEntryResource;
use App\Http\Resources\DailyReportResource;
use App\Http\Resources\HabitOccurrenceResource;
use App\Services\DailyReports\DailyReportService;
use App\Services\ToastNotificationService;
use App\ViewModels\Backoffice\DailyReports\GetDailyReportsViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DailyReportController extends Controller
{
    public function __construct(
        private readonly DailyReportService $service,
        private readonly ToastNotificationService $toastNotification,
    ) {}

    public function index(): View
    {
        return view('backoffice.daily-reports.index', [
            'json_url' => route('backoffice.daily-reports.json'),
        ]);
    }

    public function json(GetDailyReportsViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->toArray());
    }

    public function store(DailyReportRequest $request): JsonResponse
    {
        $report = $this->service->findOrCreateForDate(
            auth()->id(),
            $request->validated('report_date'),
        );

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Reporte creado'),
            message: __('El reporte diario ha sido creado'),
            timeout: 5000,
            extra: [
                'daily_report_id' => $report->daily_report_id,
                'redirect_url' => route('backoffice.daily-reports.edit', $report->daily_report_id),
            ],
        );
    }

    public function edit(int $id): View
    {
        $report = $this->service->findWithEntries($id);

        abort_unless($report && $report->user_id === auth()->id(), 404);

        return view('backoffice.daily-reports.edit', [
            'json_url' => route('backoffice.daily-reports.edit-json', $id),
            'save_entries_url' => route('backoffice.daily-reports.save-entries', $id),
            'update_report_url' => route('backoffice.daily-reports.update', $id),
            'back_url' => route('backoffice.daily-reports.index'),
        ]);
    }

    public function editJson(int $id): JsonResponse
    {
        $report = $this->service->findWithEntries($id);

        abort_unless($report && $report->user_id === auth()->id(), 404);

        $occurrences = $this->service->getOccurrencesForDate(
            auth()->id(),
            $report->getRawOriginal('report_date'),
        );

        $habits = $this->service->getActiveHabitsForUser(auth()->id());

        return response()->json([
            'report' => new DailyReportResource($report),
            'entries' => DailyReportEntryResource::collection($report->entries),
            'occurrences' => HabitOccurrenceResource::collection($occurrences),
            'habits' => $habits->map(fn ($h) => [
                'habit_id' => $h->habit_id,
                'name' => $h->name,
                'color' => $h->color,
            ]),
            'entry_statuses' => collect(\App\Enums\ReportEntryStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'moods' => collect(\App\Enums\Mood::cases())->map(fn ($m) => [
                'value' => $m->value,
                'label' => $m->label(),
                'emoji' => $m->emoji(),
            ]),
        ]);
    }

    public function update(UpdateDailyReportRequest $request, int $id): JsonResponse
    {
        $this->service->update($id, $request->validated());

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Reporte actualizado'),
            message: __('El reporte diario ha sido actualizado'),
            timeout: 5000,
        );
    }

    public function saveEntries(SaveReportEntriesRequest $request, int $id): JsonResponse
    {
        $this->service->saveEntries($id, $request->validated('entries'));

        $report = $this->service->findWithEntries($id);

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Entradas guardadas'),
            message: __('Las entradas del reporte han sido guardadas'),
            timeout: 5000,
            extra: ['entries' => DailyReportEntryResource::collection($report->entries)],
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $report = $this->service->findWithEntries($id);

        abort_unless($report && $report->user_id === auth()->id(), 404);

        $this->service->delete($id);

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Reporte eliminado'),
            message: __('El reporte diario ha sido eliminado'),
            timeout: 5000,
        );
    }
}
