<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasColor, HasLabel
{
    case PendingDeposit = 'pending_deposit';
    case Confirmed = 'confirmed';
    case PaidInFull = 'paid_in_full';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PendingDeposit => 'Awaiting deposit',
            self::Confirmed => 'Confirmed',
            self::PaidInFull => 'Paid in full',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PendingDeposit => 'warning',
            self::Confirmed => 'success',
            self::PaidInFull => 'success',
            self::Completed => 'gray',
            self::Cancelled => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
