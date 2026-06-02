<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Enums\Mood;
use App\Enums\NotificationType;
use App\Enums\ReportEntryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DailyReportRequest;
use App\Http\Requests\SaveReportEntriesRequest;
use App\Http\Requests\UpdateDailyReportRequest;
use App\Http\Resources\DailyReportEntryResource;
use App\Http\Resources\DailyReportResource;
use App\Http\Resources\HabitForReportResource;
use App\Http\Resources\HabitOccurrenceResource;
use App\Services\ToastNotificationService;
use App\ViewModels\Backoffice\DailyReports\GetDailyReportsViewModel;
use Core\BoundedContext\DailyReports\Application\Actions\DeleteDailyReport;
use Core\BoundedContext\DailyReports\Application\Actions\FindDailyReportWithEntries;
use Core\BoundedContext\DailyReports\Application\Actions\FindOrCreateDailyReportForDate;
use Core\BoundedContext\DailyReports\Application\Actions\SaveDailyReportEntries;
use Core\BoundedContext\DailyReports\Application\Actions\UpdateDailyReport;
use Core\BoundedContext\DailyReports\Application\DTOs\SaveDailyReportEntriesData;
use Core\BoundedContext\DailyReports\Application\DTOs\UpdateDailyReportData;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\DailyReportId;
use Core\BoundedContext\DailyReports\Domain\ValueObjects\ReportDate;
use Core\BoundedContext\HabitOccurrences\Application\Actions\GetOccurrencesForDate;
use Core\BoundedContext\HabitOccurrences\Domain\ValueObjects\OccurrenceDate;
use Core\BoundedContext\Habits\Application\Actions\FindActiveHabitsForUser;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

final class DailyReportController extends Controller
{
    public function __construct(
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

    public function store(
        DailyReportRequest $request,
        FindOrCreateDailyReportForDate $useCase,
    ): JsonResponse {
        $response = $useCase(
            UserId::from((int) $request->user()->user_id),
            ReportDate::fromString($request->validated('report_date')),
        );

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Reporte creado'),
            message: __('El reporte diario ha sido creado'),
            timeout: 5000,
            extra: [
                'daily_report_id' => $response->snapshot->dailyReportId,
                'redirect_url' => route(
                    'backoffice.daily-reports.edit',
                    $response->snapshot->dailyReportId,
                ),
            ],
        );
    }

    public function edit(int $id, FindDailyReportWithEntries $findReport): View
    {
        $findReport(
            DailyReportId::from($id),
            UserId::from((int) auth()->user()->user_id),
        );

        return view('backoffice.daily-reports.edit', [
            'json_url' => route('backoffice.daily-reports.edit-json', $id),
            'save_entries_url' => route('backoffice.daily-reports.save-entries', $id),
            'update_report_url' => route('backoffice.daily-reports.update', $id),
            'back_url' => route('backoffice.daily-reports.index'),
        ]);
    }

    public function editJson(
        int $id,
        FindDailyReportWithEntries $findReport,
        GetOccurrencesForDate $getOccurrences,
        FindActiveHabitsForUser $findActiveHabits,
    ): JsonResponse {
        $userId = UserId::from((int) auth()->user()->user_id);

        $reportResponse = $findReport(DailyReportId::from($id), $userId);
        $reportSnapshot = $reportResponse->snapshot;

        $occurrences = $getOccurrences(
            $userId,
            OccurrenceDate::fromString($reportSnapshot->reportDate),
        );

        $habits = $findActiveHabits->execute($userId);

        return response()->json([
            'report' => new DailyReportResource($reportSnapshot),
            'entries' => DailyReportEntryResource::collection($reportSnapshot->entries),
            'occurrences' => HabitOccurrenceResource::collection($occurrences),
            'habits' => HabitForReportResource::collection($habits),
            'entry_statuses' => collect(ReportEntryStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
            'moods' => collect(Mood::cases())->map(fn ($m) => [
                'value' => $m->value,
                'label' => $m->label(),
                'emoji' => $m->emoji(),
            ]),
        ]);
    }

    public function update(
        UpdateDailyReportRequest $request,
        int $id,
        UpdateDailyReport $useCase,
    ): JsonResponse {
        $useCase(
            DailyReportId::from($id),
            UpdateDailyReportData::fromArray($request->validated()),
        );

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Reporte actualizado'),
            message: __('El reporte diario ha sido actualizado'),
            timeout: 5000,
        );
    }

    public function saveEntries(
        SaveReportEntriesRequest $request,
        int $id,
        SaveDailyReportEntries $useCase,
    ): JsonResponse {
        $response = $useCase(
            DailyReportId::from($id),
            SaveDailyReportEntriesData::fromArray($request->validated()),
        );

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Entradas guardadas'),
            message: __('Las entradas del reporte han sido guardadas'),
            timeout: 5000,
            extra: ['entries' => DailyReportEntryResource::collection($response->snapshot->entries)],
        );
    }

    public function destroy(
        int $id,
        FindDailyReportWithEntries $findReport,
        DeleteDailyReport $delete,
    ): JsonResponse {
        $userId = UserId::from((int) auth()->user()->user_id);

        $findReport(DailyReportId::from($id), $userId);
        $delete(DailyReportId::from($id));

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Reporte eliminado'),
            message: __('El reporte diario ha sido eliminado'),
            timeout: 5000,
        );
    }
}
