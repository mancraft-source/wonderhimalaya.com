<?php

namespace App\Filament\Resources\Bookings\Schemas;

use App\Enums\BookingStatus;
use App\Filament\Resources\Trips\Schemas\TripForm;
use App\Filament\Support\CrmFields;
use App\Models\Booking;
use App\Models\Tier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()->columnSpan(2)->schema([
                    Section::make('Booking')->columns(2)->schema([
                        CrmFields::contactSelect(),
                        Select::make('company_id')->label('Partner company')->relationship('company', 'name')->searchable()->preload(),
                        Select::make('trip_id')
                            ->relationship('trip', 'title')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('trip_departure_id', null)),
                        Select::make('tier_id')->label('Tier')->options(fn () => Tier::query()->orderBy('sort')->pluck('name', 'id')),
                        CrmFields::departureSelect(),
                        TextInput::make('pax')->numeric()->minValue(1)->default(1)->required(),
                        DatePicker::make('start_date')->native(false),
                        DatePicker::make('end_date')->native(false)->afterOrEqual('start_date'),
                        Select::make('lead_id')->label('Lead')->relationship('lead', 'reference')->searchable(),
                        Select::make('quote_id')->label('Quote')->relationship('quote', 'number')->searchable(),
                    ]),
                    Section::make('Traveller notes')->columns(2)->schema([
                        Textarea::make('special_requests')->rows(3),
                        Textarea::make('emergency_contact')->rows(3)->helperText('Stored encrypted.'),
                        Textarea::make('internal_notes')->rows(3)->columnSpanFull()->helperText('Never shown to the client.'),
                    ]),
                ]),
                Group::make()->schema([
                    Section::make('Status')->schema([
                        TextInput::make('number')->disabled()->dehydrated(false)->visibleOn('edit'),
                        Select::make('status')->options(BookingStatus::class)->default(BookingStatus::PendingDeposit)->required(),
                        CrmFields::ownerSelect(),
                    ]),
                    Section::make('Money')->schema([
                        Select::make('currency')->options(TripForm::CURRENCIES)->default('USD')->required(),
                        TextInput::make('total')->numeric()->minValue(0)->required()->default(0),
                        TextInput::make('deposit_amount')->label('Deposit')->numeric()->minValue(0)->default(0),
                        DatePicker::make('balance_due_on')->native(false),
                        TextEntry::make('paid_summary')
                            ->label('Paid / balance')
                            ->visibleOn('edit')
                            ->state(fn (?Booking $record): ?string => $record
                                ? number_format((float) $record->amount_paid, 2).' paid · '.number_format($record->balanceDue(), 2).' due'
                                : null)
                            ->helperText('Updated automatically from paid payments.'),
                    ]),
                ]),
            ]);
    }
}
