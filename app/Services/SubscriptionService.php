<?php

declare(strict_types=1);

namespace App\Services;

use Core\BoundedContext\Identity\Application\Actions\ClaimGuestAccount;
use Core\BoundedContext\Identity\Application\DTOs\ClaimGuestAccountData;
use Core\BoundedContext\Identity\Application\Responses\UserResponse;
use Core\BoundedContext\Identity\Application\Services\EmailVerificationNotifier;
use Core\BoundedContext\Identity\Domain\UserRepository;
use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;
use Core\BoundedContext\Subscriptions\Application\Payment\Actions\ConfirmPayment;
use Core\BoundedContext\Subscriptions\Application\Payment\Actions\NotifyPayment;
use Core\BoundedContext\Subscriptions\Application\Payment\Actions\RejectPayment;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\ConfirmPaymentData;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\NotifyPaymentData;
use Core\BoundedContext\Subscriptions\Application\Payment\DTOs\RejectPaymentData;
use Core\BoundedContext\Subscriptions\Application\Payment\Exceptions\NoNotifiedPaymentForUser;
use Core\BoundedContext\Subscriptions\Application\Payment\PaymentReader;
use Core\BoundedContext\Subscriptions\Application\Plan\PlanCatalogReader;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;

/**
 * Thin application service for the Subscriptions HTTP layer. Keeps controllers
 * out of direct contact with the Subscriptions/Identity use cases (Controller ->
 * Service -> use cases), matching the project's Controller->Service convention.
 */
final readonly class SubscriptionService
{
    public function __construct(
        private NotifyPayment $notifyPayment,
        private ConfirmPayment $confirmPayment,
        private RejectPayment $rejectPayment,
        private PaymentReader $paymentReader,
        private ClaimGuestAccount $claimGuestAccount,
        private EmailVerificationNotifier $verificationNotifier,
        private UserRepository $users,
        private PlanCatalogReader $planReader,
    ) {}

    /**
     * Read composition the Planes view + subscription modal need: both plans'
     * info, the destination Binance email and the current user's tier/registered
     * status. A null user id means an anonymous visitor (treated as a free guest).
     *
     * @return array{
     *     plans: list<array{tier: string, amount: float, currency: string, modules: list<string>, max_habits: int|null}>,
     *     binance_payment_email: string,
     *     current_tier: string,
     *     is_guest: bool,
     *     registered: bool
     * }
     */
    public function plansPayload(?int $userId): array
    {
        $currentTier = PlanTier::FREE;
        $isGuest = true;

        if ($userId !== null) {
            $id = UserId::from($userId);
            $currentTier = $this->planReader->tierOf($id)->value();
            $isGuest = $this->users->find($id)?->isGuest() ?? true;
        }

        return [
            'plans' => [
                $this->planReader->planInfo(PlanTier::free()),
                $this->planReader->planInfo(PlanTier::unlimited()),
            ],
            'binance_payment_email' => (string) config('services.binance.payment_email'),
            'current_tier' => $currentTier,
            'is_guest' => $isGuest,
            'registered' => ! $isGuest,
        ];
    }

    /**
     * Records a notified crypto payment for the given user. Returns the new
     * payment id so the caller can surface it in the success toast.
     *
     * @param  array<string, mixed>  $data
     */
    public function notifyPayment(int $userId, array $data): int
    {
        return ($this->notifyPayment)(NotifyPaymentData::fromArray($userId, $data));
    }

    /**
     * Claims a guest account (fills name/email/password over the same user id)
     * and sends the email verification link. Returns the resulting user.
     *
     * @param  array<string, mixed>  $data
     */
    public function registerAccount(int $userId, array $data): UserResponse
    {
        $response = ($this->claimGuestAccount)(ClaimGuestAccountData::fromArray([
            ...$data,
            'user_id' => $userId,
        ]));

        $user = $this->users->find(UserId::from($response->userId));

        if ($user !== null) {
            $this->verificationNotifier->send($user);
        }

        return $response;
    }

    /**
     * Confirms the user's latest notified payment (admin action), upgrading
     * their subscription to the paid plan.
     *
     * @throws NoNotifiedPaymentForUser
     */
    public function confirmPaymentForUser(int $userId, int $adminUserId): void
    {
        $paymentId = $this->latestNotifiedPaymentIdOrFail($userId);

        ($this->confirmPayment)(new ConfirmPaymentData(
            paymentId: $paymentId,
            adminUserId: $adminUserId,
        ));
    }

    /**
     * Rejects the user's latest notified payment (admin action), returning the
     * subscription to its current active tier.
     *
     * @throws NoNotifiedPaymentForUser
     */
    public function rejectPaymentForUser(int $userId, int $adminUserId): void
    {
        $paymentId = $this->latestNotifiedPaymentIdOrFail($userId);

        ($this->rejectPayment)(new RejectPaymentData(
            paymentId: $paymentId,
            adminUserId: $adminUserId,
        ));
    }

    /**
     * @throws NoNotifiedPaymentForUser
     */
    private function latestNotifiedPaymentIdOrFail(int $userId): int
    {
        $paymentId = $this->paymentReader->latestNotifiedPaymentIdForUser(UserId::from($userId));

        if ($paymentId === null) {
            throw NoNotifiedPaymentForUser::withId($userId);
        }

        return $paymentId;
    }
}
