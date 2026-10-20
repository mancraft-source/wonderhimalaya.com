<?php

namespace App\Http\Controllers\Payments;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Setting;
use App\Payments\Exceptions\GatewayException;
use App\Payments\Gateways\ManualGateway;
use App\Payments\PaymentLog;
use App\Payments\PaymentManager;
use App\Payments\PaymentResult;
use App\Payments\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Public payment pages: /pay/{uuid}. Amounts always come from the Payment record. */
class PaymentController extends Controller
{
    public function __construct(private readonly PaymentManager $gateways, private readonly PaymentService $payments) {}

    public function show(Payment $payment): View
    {
        return view('payments.show', [
            'payment' => $payment,
            'booking' => $this->booking($payment),
            'gateways' => $payment->isPayable() ? $this->gateways->available() : [],
            'company' => (array) (Setting::get('company', []) ?: []),
        ]);
    }

    public function start(Payment $payment, string $gateway): mixed
    {
        abort_unless($this->gateways->has($gateway), 404);
        $driver = $this->gateways->gateway($gateway);
        abort_unless($driver->isAvailable(), 404);

        if (! $payment->isPayable()) {
            return redirect()->route('pay.show', $payment);
        }

        try {
            $checkout = $driver->createCheckout($payment);
        } catch (GatewayException $e) {
            PaymentLog::error('checkout.failed', ['payment' => $payment->uuid, 'gateway' => $gateway, 'error' => $e->getMessage()]);

            return redirect()->route('pay.show', $payment)
                ->with('error', 'We could not reach the payment provider. Please try again in a moment or choose another method.');
        }

        if ($checkout->instructions !== null) {
            return view('payments.instructions', [
                'payment' => $payment,
                'booking' => $this->booking($payment),
                'instructions' => $checkout->instructions,
            ]);
        }

        $payment->forceFill(['gateway' => $gateway, 'gateway_reference' => $checkout->reference ?? $payment->gateway_reference])->save();

        if ($checkout->isFormPost()) {
            return view('payments.redirect', ['action' => $checkout->formAction, 'fields' => $checkout->fields, 'gateway' => $driver]);
        }

        return redirect()->away((string) $checkout->redirectUrl);
    }

    public function return(Request $request, Payment $payment, string $gateway): RedirectResponse
    {
        abort_unless($this->gateways->has($gateway), 404);

        if ($payment->status === PaymentStatus::Paid) {
            return redirect()->route('pay.success', $payment);
        }

        try {
            $result = $this->gateways->gateway($gateway)->handleReturn($payment, $request);
        } catch (GatewayException $e) {
            PaymentLog::error('return.failed', ['payment' => $payment->uuid, 'gateway' => $gateway, 'error' => $e->getMessage()]);

            return redirect()->route('pay.show', $payment)
                ->with('error', 'We could not confirm your payment yet. If you were charged, there is no need to pay again — we will confirm by e-mail.');
        }

        $this->payments->apply($payment, $gateway, $result);
        $payment->refresh();

        return match (true) {
            $payment->status === PaymentStatus::Paid => redirect()->route('pay.success', $payment),
            $result->status === PaymentResult::PENDING => redirect()->route('pay.success', $payment)->with('status', $result->message),
            $result->status === PaymentResult::CANCELLED => redirect()->route('pay.show', $payment)->with('status', 'Payment cancelled. You can try again whenever you are ready.'),
            $result->status === PaymentResult::MISMATCH => redirect()->route('pay.show', $payment)
                ->with('error', 'We could not verify this payment automatically. Our team has been alerted and will contact you.'),
            default => redirect()->route('pay.show', $payment)->with('error', $result->message ?: 'The payment did not go through. Please try again.'),
        };
    }

    public function cancel(Payment $payment): RedirectResponse
    {
        PaymentLog::info('checkout.cancelled', ['payment' => $payment->uuid, 'gateway' => $payment->gateway]);

        return redirect()->route('pay.show', $payment)->with('status', 'Payment cancelled. You can try again whenever you are ready.');
    }

    public function success(Payment $payment): View
    {
        return view('payments.success', [
            'payment' => $payment,
            'booking' => $this->booking($payment),
            'isManual' => $payment->gateway === app(ManualGateway::class)->key(),
        ]);
    }

    private function booking(Payment $payment): ?Booking
    {
        $payable = $payment->payable;

        return $payable instanceof Booking ? $payable->load(['trip.media', 'departure', 'contact']) : null;
    }
}
