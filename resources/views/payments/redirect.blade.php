{{-- Auto-submitted, signed form POST to a hosted bank checkout (Cybersource Secure Acceptance). --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Redirecting to secure payment…</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f4efe6; color: #1f3a33; font-family: system-ui, sans-serif; text-align: center; }
        button { margin-top: 1rem; border: 0; border-radius: 999px; background: #b5532e; color: #fff; padding: .9rem 1.6rem; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>
    <form id="gateway-form" method="POST" action="{{ $action }}">
        @foreach ($fields as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <p>Taking you to {{ $gateway->label() }} secure checkout…</p>
        <noscript><p>JavaScript is disabled — please press the button.</p></noscript>
        <button type="submit">Continue to payment</button>
    </form>
    <script @if (app()->bound('csp-nonce')) nonce="{{ app('csp-nonce') }}" @endif>document.getElementById('gateway-form').submit();</script>
</body>
</html>
