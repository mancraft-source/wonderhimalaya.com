<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadStatus: string implements HasColor, HasLabel
{
    case New = 'new';
    case Contacted = 'contacted';
    case Qualified = 'qualified';
    case Quoted = 'quoted';
    case DepositPaid = 'deposit_paid';
    case Confirmed = 'confirmed';
    case Travelled = 'travelled';
    case Lost = 'lost';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Quoted => 'Quoted',
            self::DepositPaid => 'Deposit paid',
            self::Confirmed => 'Confirmed',
            self::Travelled => 'Travelled',
            self::Lost => 'Lost',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::New => 'info',
            self::Contacted => 'primary',
            self::Qualified => 'primary',
            self::Quoted => 'warning',
            self::DepositPaid => 'success',
            self::Confirmed => 'success',
            self::Travelled => 'gray',
            self::Lost => 'danger',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
