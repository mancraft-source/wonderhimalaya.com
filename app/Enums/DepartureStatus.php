<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DepartureStatus: string implements HasColor, HasLabel
{
    case Available = 'available';
    case Guaranteed = 'guaranteed';
    case Limited = 'limited';
    case SoldOut = 'sold_out';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Guaranteed => 'Guaranteed',
            self::Limited => 'Limited seats',
            self::SoldOut => 'Sold out',
            self::Cancelled => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Available => 'success',
            self::Guaranteed => 'primary',
            self::Limited => 'warning',
            self::SoldOut => 'danger',
            self::Cancelled => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
