<?php

declare(strict_types=1);

namespace Core\BoundedContext\Subscriptions\Application\Payment;

use Core\BoundedContext\Identity\Domain\ValueObjects\Concretes\UserId;

/**
 * Application-facing read port for payments. Answers the admin's question "which
 * payment, if any, is this user waiting to have reconciled?" so the backoffice
 * can confirm/reject by user id (the UI lists users, not raw payments).
 */
interface PaymentReader
{
    /**
     * The id of the user's most recent payment still awaiting reconciliation
     * (status = payment_notified), or null when there is nothing to confirm.
     */
    public function latestNotifiedPaymentIdForUser(UserId $userId): ?int;

    /**
     * Bulk counterpart for a page of users: which of the given users have a
     * payment still awaiting reconciliation (status = payment_notified). One
     * query instead of N.
     *
     * @param  list<int>  $userIds
     * @return list<int> the subset of user ids with a notified payment
     */
    public function usersWithNotifiedPayment(array $userIds): array;
}
