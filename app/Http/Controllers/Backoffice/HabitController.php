<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\HabitRequest;
use App\Http\Resources\HabitResource;
use App\Services\ToastNotificationService;
use App\ViewModels\Backoffice\Habits\GetHabitsViewModel;
use Core\BoundedContext\Habits\Application\Actions\CreateHabit;
use Core\BoundedContext\Habits\Application\Actions\DeleteHabit;
use Core\BoundedContext\Habits\Application\Actions\UpdateHabit;
use Core\BoundedContext\Habits\Application\DTOs\CreateHabitData;
use Core\BoundedContext\Habits\Application\DTOs\UpdateHabitData;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HabitController extends Controller
{
    public function __construct(private readonly ToastNotificationService $toastNotification) {}

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

    public function store(HabitRequest $request, CreateHabit $createHabit): JsonResponse
    {
        $response = $createHabit(
            CreateHabitData::fromArray([
                ...$request->validated(),
                'user_id' => (int) auth()->id(),
            ]),
        );

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Habito creado'),
            message: __('El habito :name ha sido creado', ['name' => $response->name]),
            timeout: 5000,
            extra: [
                'habit_id' => $response->habitId,
                'habit' => (new HabitResource($response))->resolve(),
            ],
        );
    }

    public function update(HabitRequest $request, int $id, UpdateHabit $updateHabit): JsonResponse
    {
        $updateHabit(
            UpdateHabitData::fromArray([
                ...$request->validated(),
                'habit_id' => $id,
                'user_id' => (int) auth()->id(),
            ]),
        );

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Habito actualizado'),
            message: __('El habito ha sido actualizado con exito'),
            timeout: 5000,
        );
    }

    public function destroy(int $id, DeleteHabit $deleteHabit): JsonResponse
    {
        $deleteHabit($id, (int) auth()->id());

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Habito eliminado'),
            message: __('El habito ha sido eliminado con exito'),
            timeout: 5000,
        );
    }
}
