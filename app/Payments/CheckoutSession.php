<?php

namespace App\Payments;

/**
 * What the payment page should do next: redirect the customer, auto-submit a
 * signed form POST (bank gateways), or show offline instructions.
 */
final readonly class CheckoutSession
{
    /**
     * @param  array<string, string>  $fields
     */
    public function __construct(
        public ?string $redirectUrl = null,
        public ?string $formAction = null,
        public array $fields = [],
        public ?string $reference = null,
        public ?string $instructions = null,
    ) {}

    public static function redirect(string $url, ?string $reference = null): self
    {
        return new self(redirectUrl: $url, reference: $reference);
    }

    /** @param array<string, string> $fields */
    public static function post(string $action, array $fields, ?string $reference = null): self
    {
        return new self(formAction: $action, fields: $fields, reference: $reference);
    }

    public static function instructions(string $text): self
    {
        return new self(instructions: $text);
    }

    public function isRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }

    public function isFormPost(): bool
    {
        return $this->formAction !== null;
    }
}
