<?php

namespace App\Filament\Resources\Bookings;

use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Bookings\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\Bookings\RelationManagers\TravellersRelationManager;
use App\Filament\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Resources\Bookings\Tables\BookingsTable;
use App\Filament\Support\GlobalSearch;
use App\Filament\Support\SearchesPhoneNumbers;
use App\Models\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class BookingResource extends Resource
{
    use SearchesPhoneNumbers;

    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'number';

    protected static int $globalSearchResultsLimit = GlobalSearch::RESULTS_LIMIT;

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'contact.first_name', 'contact.last_name', 'contact.email', 'contact.phone', 'contact.whatsapp'];
    }

    /** @return array<int, string> */
    protected static function getGlobalSearchPhoneColumns(): array
    {
        return ['contact.phone', 'contact.whatsapp'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Booking $record */
        return trim($record->number.' — '.($record->contact?->name ?: $record->contact?->email), ' —');
    }

    /** e.g. "Everest Base Camp Trek · 12 Mar 2027 · Balance USD 1,200 · Deposit paid" */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Booking $record */
        $balance = $record->balanceDue();

        return GlobalSearch::line([
            $record->trip?->title,
            $record->start_date?->format('j M Y'),
            $balance > 0 ? 'Balance '.GlobalSearch::money($balance, $record->currency) : 'Paid in full',
            $record->status?->getLabel(),
        ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['contact', 'trip'])->latest();
    }

    public static function getRelations(): array
    {
        return [
            TravellersRelationManager::class,
            PaymentsRelationManager::class,
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
