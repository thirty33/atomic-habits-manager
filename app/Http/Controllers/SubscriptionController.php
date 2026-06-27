<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\NotificationType;
use App\Http\Requests\Subscriptions\NotifyPaymentRequest;
use App\Http\Requests\Subscriptions\RegisterSubscriptionRequest;
use App\Services\SubscriptionService;
use App\Services\ToastNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Public (guest-reachable) HTTP surface for the Subscriptions module: the Planes
 * view + its JSON payload, plus the authenticated "notify payment" and
 * "register/claim account" actions. Controller -> Service -> use cases.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private readonly ToastNotificationService $toastNotification,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function plans(): View
    {
        return view('subscriptions.plans', [
            'json_url' => route('subscriptions.plans.json'),
        ]);
    }

    /**
     * Data the Planes view + subscription modal need: both plans' info, the
     * destination Binance email and the current user's tier. Composed in the
     * Service layer (Controller -> Service).
     */
    public function plansJson(): JsonResponse
    {
        $userId = auth()->id();

        return response()->json(
            $this->subscriptionService->plansPayload($userId !== null ? (int) $userId : null),
        );
    }

    public function notifyPayment(NotifyPaymentRequest $request): JsonResponse
    {
        $paymentId = $this->subscriptionService->notifyPayment(
            (int) auth()->id(),
            $request->validated(),
        );

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Pago notificado'),
            message: __('Hemos registrado tu pago. Lo verificaremos y activaremos tu plan a la brevedad.'),
            timeout: 6000,
            extra: [
                'payment_id' => $paymentId,
            ],
        );
    }

    public function register(RegisterSubscriptionRequest $request): JsonResponse
    {
        $user = $this->subscriptionService->registerAccount(
            (int) auth()->id(),
            $request->validated(),
        );

        return $this->toastNotification->notify(
            type: NotificationType::SUCCESS,
            title: __('Cuenta registrada'),
            message: __('Te enviamos un correo para confirmar tu cuenta.'),
            timeout: 6000,
            extra: [
                'user_id' => $user->userId,
                'email' => $user->email,
            ],
        );
    }
}
