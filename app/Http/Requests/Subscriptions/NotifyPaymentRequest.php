<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscriptions;

use Core\BoundedContext\Subscriptions\Domain\Plan\ValueObjects\PlanTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a "notify payment" submission. Only syntactic checks live here; the
 * Binance email's domain validity is a domain rule (BinanceEmail VO throwing
 * InvalidBinanceEmail -> 422), so no `email` rule is forced on it to avoid
 * pre-empting the domain message. The plan_tier allow-list is restricted to
 * payable tiers as defense-in-depth; the authoritative "only a paid plan may be
 * notified" rule lives in the NotifyPayment use case.
 */
final class NotifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'payer_binance_email' => ['required', 'string', 'max:255'],
            'tx_reference' => ['nullable', 'string', 'max:191'],
            'plan_tier' => ['sometimes', Rule::in([PlanTier::UNLIMITED])],
        ];
    }

    /**
     * Defaults the plan to unlimited (the only paid tier for now).
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('plan_tier')) {
            $this->merge(['plan_tier' => PlanTier::UNLIMITED]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payer_binance_email' => __('Correo de Binance'),
            'tx_reference' => __('Referencia de transacción'),
            'plan_tier' => __('Plan'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'payer_binance_email.required' => __('El campo :attribute es obligatorio.'),
            'plan_tier.in' => __('El plan seleccionado no es válido.'),
        ];
    }
}
