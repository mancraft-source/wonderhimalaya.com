<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentType: string implements HasColor, HasLabel
{
    case Deposit = 'deposit';
    case Balance = 'balance';
    case Custom = 'custom';

    public function getLabel(): string
    {
        return match ($this) {
            self::Deposit => 'Deposit',
            self::Balance => 'Balance',
            self::Custom => 'Custom amount',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Deposit => 'primary',
            self::Balance => 'success',
            self::Custom => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
