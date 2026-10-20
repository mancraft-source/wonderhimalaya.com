<?php

namespace App\Rules;

use App\Support\Turnstile as TurnstileVerifier;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Usage: 'cf-turnstile-response' => Turnstile::rules()
 * (required + verified when a secret is configured, ignored otherwise).
 */
class Turnstile implements ValidationRule
{
    public function __construct(private readonly ?string $ip = null) {}

    /** @return array<int, mixed> */
    public static function rules(): array
    {
        return TurnstileVerifier::enabled() ? ['required', 'string', new self] : ['nullable'];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! TurnstileVerifier::verify(is_string($value) ? $value : null, $this->ip ?? request()->ip())) {
            $fail('Please confirm you are human and try again.');
        }
    }
}
