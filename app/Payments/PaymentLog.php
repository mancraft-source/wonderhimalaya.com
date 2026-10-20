<?php

namespace App\Payments;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Writes gateway interactions to the dedicated `payments` log channel.
 * Context is scrubbed: never log secrets, signatures, tokens or card data.
 */
final class PaymentLog
{
    private const REDACT = [
        'access_token', 'client_secret', 'secret', 'secret_key', 'signature', 'access_key', 'authorization',
        'card_number', 'req_card_number', 'card_cvn', 'req_card_expiry_date', 'cvv', 'password', 'payer_authentication_cavv',
    ];

    /** @param array<string, mixed> $context */
    public static function info(string $message, array $context = []): void
    {
        Log::channel(config('payments.log_channel', 'payments'))->info($message, self::scrub($context));
    }

    /** @param array<string, mixed> $context */
    public static function warning(string $message, array $context = []): void
    {
        Log::channel(config('payments.log_channel', 'payments'))->warning($message, self::scrub($context));
    }

    /** @param array<string, mixed> $context */
    public static function error(string $message, array $context = []): void
    {
        Log::channel(config('payments.log_channel', 'payments'))->error($message, self::scrub($context));
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACT, true)) {
                $data[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $data[$key] = self::scrub($value);
            }
        }

        return Arr::except($data, ['links']);
    }
}
