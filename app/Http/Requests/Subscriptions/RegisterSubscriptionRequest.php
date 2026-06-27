<?php

declare(strict_types=1);

namespace App\Http\Requests\Subscriptions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validates the registration/claim form shown in the subscription modal. Email
 * uniqueness is intentionally NOT a `Rule::unique` here: it is a domain rule
 * enforced by ClaimGuestAccount via the repository, which throws
 * EmailAlreadyTaken (mapped to a 422 on `email` in bootstrap/app.php).
 */
final class RegisterSubscriptionRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Nombre'),
            'email' => __('Correo'),
            'password' => __('Contraseña'),
        ];
    }
}
