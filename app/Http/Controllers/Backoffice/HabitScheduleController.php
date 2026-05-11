<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HabitScheduleRequest;
use App\Http\Requests\UpdateHabitScheduleRequest;
use App\Services\ToastNotificationService;
use Core\BoundedContext\HabitSchedules\Application\Actions\CreateHabitSchedule;
use Core\BoundedContext\HabitSchedules\Application\Actions\UpdateHabitSchedule;
use Core\BoundedContext\HabitSchedules\Application\DTOs\CreateHabitScheduleData;
use Core\BoundedContext\HabitSchedules\Application\DTOs\UpdateHabitScheduleData;
use Illuminate\Http\JsonResponse;

class HabitScheduleController extends Controller
{
    public function __construct(private readonly ToastNotificationService $toastNotification) {}

    public function store(HabitScheduleRequest $request, CreateHabitSchedule $useCase): JsonResponse
    {
        $useCase(CreateHabitScheduleData::fromArray($request->validated()));

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Programación guardada'),
            message: __('El hábito ha sido programado correctamente'),
            timeout: 5000,
        );
    }

    public function update(
        UpdateHabitScheduleRequest $request,
        UpdateHabitSchedule $useCase,
        int $id,
    ): JsonResponse {
        $useCase(UpdateHabitScheduleData::fromArray($id, $request->validated()));

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Programación actualizada'),
            message: __('La programación ha sido actualizada correctamente'),
            timeout: 5000,
        );
    }
}
