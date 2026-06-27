<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backoffice;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use App\Services\ToastNotificationService;
use App\Services\UserService;
use App\ViewModels\Backoffice\Users\GetUsersViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pure orchestration: the whole Users module (index/json/activation/confirm-
 * payment/reject-payment) is gated by the EnsureCanManageUsers middleware
 * (single source of authorization via the Access BC), so the controller carries
 * no inline capability checks.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly ToastNotificationService $toastNotification,
        private readonly UserService $userService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function index(): View
    {
        return view('backoffice.users.index', [
            'json_url' => route('backoffice.users.json'),
        ]);
    }

    public function json(GetUsersViewModel $viewModel): JsonResponse
    {
        return response()->json($viewModel->toArray());
    }

    public function activation(Request $request, int $id): JsonResponse
    {
        $activate = $request->boolean('is_active');

        $user = $this->userService->toggleActivation($id, $activate);

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: $user->isActive ? __('Usuario activado') : __('Usuario desactivado'),
            message: $user->isActive
                ? __('El usuario :name ha sido activado', ['name' => $user->name])
                : __('El usuario :name ha sido desactivado', ['name' => $user->name]),
            timeout: 5000,
        );
    }

    public function confirmPayment(int $id): JsonResponse
    {
        $this->subscriptionService->confirmPaymentForUser($id, (int) auth()->id());

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Pago confirmado'),
            message: __('El plan del usuario ha sido activado.'),
            timeout: 5000,
        );
    }

    public function rejectPayment(int $id): JsonResponse
    {
        $this->subscriptionService->rejectPaymentForUser($id, (int) auth()->id());

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Pago rechazado'),
            message: __('El pago notificado ha sido rechazado.'),
            timeout: 5000,
        );
    }
}
