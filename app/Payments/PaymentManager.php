<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/** Resolves gateways by key from config('payments.gateways'). */
class PaymentManager
{
    /** @var array<string, PaymentGateway> */
    private array $resolved = [];

    public function __construct(private readonly Container $container) {}

    public function has(string $key): bool
    {
        return array_key_exists($key, config('payments.gateways', []));
    }

    public function gateway(string $key): PaymentGateway
    {
        if (! $this->has($key)) {
            throw new InvalidArgumentException("Unknown payment gateway [{$key}].");
        }

        return $this->resolved[$key] ??= $this->container->make(config('payments.gateways')[$key]);
    }

    /** @return array<string, PaymentGateway> configured gateways, in display order */
    public function available(): array
    {
        $gateways = [];

        foreach (array_keys(config('payments.gateways', [])) as $key) {
            $gateway = $this->gateway($key);

            if ($gateway->isAvailable()) {
                $gateways[$key] = $gateway;
            }
        }

        return $gateways;
    }
}
