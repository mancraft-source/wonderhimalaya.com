<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CompanyType: string implements HasColor, HasLabel
{
    case Agency = 'agency';
    case TourOperator = 'tour_operator';
    case Corporate = 'corporate';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            self::Agency => 'Travel agency',
            self::TourOperator => 'Tour operator',
            self::Corporate => 'Corporate / MICE',
            self::Other => 'Other',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Agency => 'primary',
            self::TourOperator => 'info',
            self::Corporate => 'warning',
            self::Other => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
