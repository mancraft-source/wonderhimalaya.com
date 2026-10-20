<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Payments\Exceptions\GatewayException;
use App\Payments\Exceptions\InvalidWebhookSignature;
use App\Payments\PaymentLog;
use App\Payments\PaymentManager;
use App\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Server-to-server gateway notifications: POST /webhooks/payments/{gateway}. */
class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, PaymentManager $gateways, PaymentService $payments): JsonResponse
    {
        abort_unless($gateways->has($gateway), 404);

        try {
            $result = $gateways->gateway($gateway)->handleWebhook($request);
        } catch (InvalidWebhookSignature) {
            return response()->json(['error' => 'invalid signature'], 400);
        } catch (GatewayException $e) {
            PaymentLog::error('webhook.verify_failed', ['gateway' => $gateway, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'verification unavailable'], 503); // gateway will retry
        }

        if (! $result || ! $result->paymentUuid) {
            return response()->json(['status' => 'ignored']);
        }

        $payment = Payment::where('uuid', $result->paymentUuid)->first();

        if (! $payment) {
            PaymentLog::warning('webhook.unknown_payment', ['gateway' => $gateway, 'payment' => $result->paymentUuid]);

            return response()->json(['status' => 'unknown payment']);
        }

        $payments->apply($payment, $gateway, $result);

        return response()->json(['status' => 'ok']);
    }
}
