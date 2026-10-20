<?php

namespace App\Actions\Quotes;

use App\Enums\LeadStatus;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Notifications\QuoteSent;
use Illuminate\Support\Facades\Notification;

/** Marks a quote as sent, moves the lead to "quoted" and e-mails the public quote link. */
class SendQuote
{
    public function handle(Quote $quote, ?string $personalMessage = null): Quote
    {
        $quote->loadMissing('contact', 'lead');

        if (in_array($quote->status, [QuoteStatus::Draft, QuoteStatus::Expired], true)) {
            $quote->status = QuoteStatus::Sent;
        }

        $quote->sent_at = now();
        $quote->save();

        if ($quote->lead && in_array($quote->lead->status, [LeadStatus::New, LeadStatus::Contacted, LeadStatus::Qualified], true)) {
            $quote->lead->update(['status' => LeadStatus::Quoted]);
        }

        Notification::route('mail', $quote->contact->email)->notify(new QuoteSent($quote, $personalMessage));

        return $quote;
    }
}
