<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** A booking left "pending deposit" (deposit or full payment received). Fired once. */
class BookingConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Booking $booking) {}
}
