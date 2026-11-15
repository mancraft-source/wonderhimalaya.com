<?php

namespace App\Filament\Resources\Contacts;

use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\Resources\Contacts\Pages\CreateContact;
use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Contacts\RelationManagers\BookingsRelationManager;
use App\Filament\Resources\Contacts\RelationManagers\LeadsRelationManager;
use App\Filament\Resources\Contacts\Schemas\ContactForm;
use App\Filament\Resources\Contacts\Tables\ContactsTable;
use App\Filament\Support\GlobalSearch;
use App\Filament\Support\SearchesPhoneNumbers;
use App\Models\Contact;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ContactResource extends Resource
{
    use SearchesPhoneNumbers;

    protected static ?string $model = Contact::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'email';

    protected static int $globalSearchResultsLimit = GlobalSearch::RESULTS_LIMIT;

    public static function form(Schema $schema): Schema
    {
        return ContactForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LeadsRelationManager::class,
            BookingsRelationManager::class,
            NotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'edit' => EditContact::route('/{record}/edit'),
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email', 'phone', 'whatsapp'];
    }

    /** @return array<int, string> */
    protected static function getGlobalSearchPhoneColumns(): array
    {
        return ['phone', 'whatsapp'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Contact $record */
        return $record->name ?: $record->email;
    }

    /** e.g. "jane@example.com · +44 7700 900123 · Himalayan Partners · United Kingdom" */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Contact $record */
        return GlobalSearch::line([
            $record->email,
            $record->phone ?: $record->whatsapp,
            $record->company?->name,
            $record->country,
        ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('company')->latest();
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
