<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\Frontend\FormActionGenerator;
use App\Services\Frontend\UIElements\ActionForm;
use Carbon\Carbon;
use Core\BoundedContext\Identity\Application\Responses\UserResponse;
use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transform View for the backoffice Users module. Maps a UserResponse (read DTO)
 * to the datatable JSON shape: identity fields, an active/verified status, the
 * subscription plan tier (composed from the Subscriptions PlanCatalogReader by
 * the ViewModel) and the activation toggle action.
 *
 * The toggle action is baked as `delete_action` so it can reuse the existing
 * confirm modal (`AppRemover`, which fires `model.delete_action`). The desired
 * next state travels in the query string, so no request body is needed.
 */
final class UserResource extends JsonResource
{
    private FormActionGenerator $formActionGenerator;

    public function __construct(
        UserResponse $resource,
        private readonly string $planTier,
        private readonly bool $hasNotifiedPayment = false,
    ) {
        parent::__construct($resource);
        $this->formActionGenerator = new FormActionGenerator;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var UserResponse $r */
        $r = $this->resource;

        $nextActive = $r->isActive ? 0 : 1;

        return [
            'pk_name' => 'user_id',
            'user_id' => $r->userId,
            'name' => $r->name,
            'email' => $r->email,
            'is_active' => $r->isActive,
            'is_admin' => $r->isAdmin,
            'is_verified' => $r->isVerified,
            'plan_tier' => $this->planTier,
            'plan_tier_label' => $this->planTier === PlanTier::UNLIMITED
                ? __('Ilimitado')
                : __('Gratis'),
            'has_notified_payment' => $this->hasNotifiedPayment,
            'created_at' => $r->createdAt !== null
                ? Carbon::parse($r->createdAt)->format('Y-m-d')
                : null,
            'created_at_iso_format_ll' => $r->createdAt !== null
                ? Carbon::parse($r->createdAt)->isoFormat('LL')
                : null,
            'delete_action' => $this->formActionGenerator->setActionForm(
                new ActionForm(
                    url: route('backoffice.users.activation', $r->userId).'?is_active='.$nextActive,
                    method: FormActionGenerator::HTTP_METHOD_PUT,
                )
            )->getActionForm(),
            'confirm_payment_action' => (new FormActionGenerator)->setActionForm(
                new ActionForm(
                    url: route('backoffice.users.confirm-payment', $r->userId),
                    method: FormActionGenerator::HTTP_METHOD_PUT,
                )
            )->getActionForm(),
            'reject_payment_action' => (new FormActionGenerator)->setActionForm(
                new ActionForm(
                    url: route('backoffice.users.reject-payment', $r->userId),
                    method: FormActionGenerator::HTTP_METHOD_PUT,
                )
            )->getActionForm(),
        ];
    }
}
