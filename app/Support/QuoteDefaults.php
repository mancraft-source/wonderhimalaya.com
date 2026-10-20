<?php

namespace App\Support;

use App\Models\Setting;

/** Defaults used when drafting quotes in the admin. */
final class QuoteDefaults
{
    public const TERMS = <<<'HTML'
<p><strong>Booking &amp; payment.</strong> A deposit is required to confirm your booking. The balance is due no later than the date shown on your booking confirmation.</p>
<p><strong>Cancellations.</strong> Deposits are non-refundable. Cancellations made less than 30 days before departure may forfeit the full trip price. We strongly recommend comprehensive travel insurance covering helicopter evacuation up to 6,000&nbsp;m.</p>
<p><strong>Changes.</strong> Itineraries in the Himalaya can change due to weather, flight delays or trail conditions. Your guide will always put your safety first; additional costs caused by events outside our control are the responsibility of the traveller.</p>
<p><strong>Prices.</strong> Prices are per person in the stated currency and are valid until the date shown on this quote.</p>
HTML;

    public static function terms(): string
    {
        return (string) (Setting::get('quotes.terms') ?: self::TERMS);
    }

    public static function depositPercent(): int
    {
        return (int) Setting::get('booking.deposit_percent', 20);
    }

    public static function currency(): string
    {
        return (string) Setting::get('booking.currency', 'USD');
    }
}
