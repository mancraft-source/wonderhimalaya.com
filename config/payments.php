<?php

use App\Payments\Gateways\ManualGateway;
use App\Payments\Gateways\NepalBankGateway;
use App\Payments\Gateways\PayPalGateway;

return [

    /*
     * Gateways offered on the public payment page, in display order.
     * Key => class implementing App\Payments\Contracts\PaymentGateway.
     * Credentials live in config/services.php.
     */
    'gateways' => [
        'paypal' => PayPalGateway::class,
        'card' => NepalBankGateway::class,
        'bank_transfer' => ManualGateway::class,
    ],

    // Days until a newly created payment link expires.
    'link_ttl_days' => (int) env('PAYMENT_LINK_TTL_DAYS', 14),

    // Default days before departure that the balance is due (Setting `booking.balance_due_days` overrides).
    'balance_due_days' => 30,

    // Balance reminders are sent when balance_due_on is within this many days.
    'balance_reminder_window_days' => 7,

    'log_channel' => 'payments',
];
