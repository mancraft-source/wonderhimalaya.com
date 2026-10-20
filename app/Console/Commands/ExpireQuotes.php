<?php

namespace App\Console\Commands;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('quotes:expire')]
#[Description('Mark sent/viewed quotes past their validity date as expired')]
class ExpireQuotes extends Command
{
    public function handle(): int
    {
        $count = 0;

        Quote::query()
            ->whereIn('status', [QuoteStatus::Sent, QuoteStatus::Viewed])
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<', today())
            ->chunkById(200, function ($quotes) use (&$count) {
                foreach ($quotes as $quote) {
                    $quote->update(['status' => QuoteStatus::Expired]);
                    $count++;
                }
            });

        $this->info("Expired {$count} quote(s).");

        return self::SUCCESS;
    }
}
