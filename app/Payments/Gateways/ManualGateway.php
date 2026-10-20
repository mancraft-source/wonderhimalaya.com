<?php

namespace App\Payments\Gateways;

use App\Models\Payment;
use App\Models\Setting;
use App\Payments\CheckoutSession;
use App\Payments\Contracts\PaymentGateway;
use App\Payments\PaymentResult;
use Illuminate\Http\Request;

/**
 * Offline bank transfer: shows instructions (Setting `bank_transfer`) with the payment
 * reference. Nothing is marked paid automatically — staff reconcile and mark it in the CRM.
 */
class ManualGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'bank_transfer';
    }

    public function label(): string
    {
        return 'Bank transfer';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function instructions(): string
    {
        $text = Setting::get('bank_transfer');

        if (is_array($text)) {
            $text = $text['instructions'] ?? $text['text'] ?? null;
        }

        return filled($text)
            ? (string) $text
            : 'Please contact us for our bank account details. Quote your payment reference so we can match your transfer.';
    }

    public function createCheckout(Payment $payment): CheckoutSession
    {
        return CheckoutSession::instructions($this->instructions());
    }

    public function handleReturn(Payment $payment, Request $request): PaymentResult
    {
        return PaymentResult::pending('We will confirm your booking as soon as your transfer arrives.');
    }

    public function handleWebhook(Request $request): ?PaymentResult
    {
        return null;
    }
}
