<?php

namespace App\Http\Controllers\Backoffice;

use App\Actions\HabitSchedules\CreateHabitScheduleAction;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HabitScheduleRequest;
use App\Services\ToastNotificationService;
use Illuminate\Http\JsonResponse;

class HabitScheduleController extends Controller
{
    public function __construct(private readonly ToastNotificationService $toastNotification)
    {
    }

    public function store(HabitScheduleRequest $request): JsonResponse
    {
        CreateHabitScheduleAction::execute($request->validated());

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Programación guardada'),
            message: __('El hábito ha sido programado correctamente'),
            timeout: 5000,
        );
    }
}