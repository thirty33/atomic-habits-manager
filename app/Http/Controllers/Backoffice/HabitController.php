<?php

namespace App\Http\Controllers\Backoffice;

use App\Actions\Habits\CreateHabitAction;
use App\Actions\Habits\DeleteHabitAction;
use App\Actions\Habits\UpdateHabitAction;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HabitRequest;
use App\Services\ToastNotificationService;
use App\ViewModels\Backoffice\Habits\GetHabitsViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HabitController extends Controller
{
    public function __construct(private readonly ToastNotificationService $toastNotification)
    {
    }

    public function index(): View
    {
        return view('backoffice.habits.index', [
            'json_url' => route('backoffice.habits.json'),
        ]);
    }

    public function json(GetHabitsViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->toArray());
    }

    public function store(HabitRequest $request): JsonResponse
    {
        $habit = CreateHabitAction::execute($request->validated());

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Habito creado'),
            message: __('El habito :name ha sido creado', ['name' => $habit->name]),
            timeout: 5000,
            extra: ['habit_id' => $habit->habit_id],
        );
    }

    public function update(HabitRequest $request, int $id): JsonResponse
    {
        UpdateHabitAction::execute($id, $request->validated());

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Habito actualizado'),
            message: __('El habito ha sido actualizado con exito'),
            timeout: 5000,
        );
    }

    public function destroy(int $id): JsonResponse
    {
        DeleteHabitAction::execute($id);

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Habito eliminado'),
            message: __('El habito ha sido eliminado con exito'),
            timeout: 5000,
        );
    }
}