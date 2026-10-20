<?php

namespace App\Filament\Support;

use App\Models\Contact;
use App\Models\TripDeparture;
use App\Models\User;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;

final class CrmFields
{
    public static function contactSelect(string $name = 'contact_id'): Select
    {
        return Select::make($name)
            ->label('Contact')
            ->relationship('contact', 'email')
            ->getOptionLabelFromRecordUsing(fn (Contact $record): string => trim($record->name.' <'.$record->email.'>'))
            ->searchable(['first_name', 'last_name', 'email', 'phone'])
            ->required()
            ->createOptionForm(self::contactFields())
            ->createOptionModalHeading('New contact');
    }

    /** @return array<int, Field> */
    public static function contactFields(): array
    {
        return [
            TextInput::make('first_name')->required()->maxLength(255),
            TextInput::make('last_name')->maxLength(255),
            TextInput::make('email')->email()->required()->maxLength(255),
            TextInput::make('phone')->tel()->maxLength(50),
            TextInput::make('country')->maxLength(100),
        ];
    }

    /** Active staff who can own CRM records. */
    public static function staffOptions(): Collection
    {
        return User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id');
    }

    public static function ownerSelect(string $name = 'owner_id'): Select
    {
        return Select::make($name)
            ->label('Owner')
            ->options(fn () => self::staffOptions())
            ->searchable()
            ->default(fn () => auth()->id());
    }

    /** Departure select limited to the trip chosen in the same form. */
    public static function departureSelect(string $tripField = 'trip_id'): Select
    {
        return Select::make('trip_departure_id')
            ->label('Departure')
            ->options(fn (Get $get): array => $get($tripField)
                ? TripDeparture::query()
                    ->where('trip_id', $get($tripField))
                    ->orderBy('start_date')
                    ->get()
                    ->mapWithKeys(fn (TripDeparture $d) => [$d->id => $d->start_date->format('D j M Y').' ('.$d->seatsLeft().' seats left)'])
                    ->all()
                : [])
            ->disabled(fn (Get $get): bool => blank($get($tripField)))
            ->searchable();
    }

    public static function whatsappUrl(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return $digits ? 'https://wa.me/'.$digits : null;
    }
}
