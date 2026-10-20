<?php

namespace App\Support;

use Illuminate\Support\Number;

final class Money
{
    public static function format(mixed $amount, ?string $currency = 'USD'): string
    {
        $currency = strtoupper($currency ?: 'USD');

        if (extension_loaded('intl')) {
            return (string) Number::currency((float) $amount, $currency, 'en');
        }

        return $currency.' '.number_format((float) $amount, 2);
    }
}
