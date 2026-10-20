<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum LeadType: string implements HasColor, HasLabel
{
    case TailorMade = 'tailor_made';
    case TripEnquiry = 'trip_enquiry';
    case Departure = 'departure';
    case PrivateTrip = 'private_trip';
    case B2B = 'b2b';
    case Contact = 'contact';

    public function getLabel(): string
    {
        return match ($this) {
            self::TailorMade => 'Tailor-made',
            self::TripEnquiry => 'Trip enquiry',
            self::Departure => 'Group departure',
            self::PrivateTrip => 'Private trip',
            self::B2B => 'B2B partner',
            self::Contact => 'Contact form',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::TailorMade => 'primary',
            self::TripEnquiry => 'info',
            self::Departure => 'success',
            self::PrivateTrip => 'warning',
            self::B2B => 'danger',
            self::Contact => 'gray',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
