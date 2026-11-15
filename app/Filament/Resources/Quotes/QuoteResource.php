<?php

namespace App\Filament\Resources\Quotes;

use App\Filament\Resources\Quotes\Pages\CreateQuote;
use App\Filament\Resources\Quotes\Pages\EditQuote;
use App\Filament\Resources\Quotes\Pages\ListQuotes;
use App\Filament\Resources\Quotes\Pages\ViewQuote;
use App\Filament\Resources\Quotes\Schemas\QuoteForm;
use App\Filament\Resources\Quotes\Tables\QuotesTable;
use App\Filament\Support\GlobalSearch;
use App\Models\Quote;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'number';

    protected static int $globalSearchResultsLimit = GlobalSearch::RESULTS_LIMIT;

    public static function form(Schema $schema): Schema
    {
        return QuoteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotesTable::configure($table);
    }

    /** Totals are always computed server-side from the saved line items. */
    public static function recalculate(Quote $quote): void
    {
        $quote->unsetRelation('items');
        $quote->recalculate();
        $quote->save();
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['number', 'title', 'contact.first_name', 'contact.last_name', 'contact.email'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Quote $record */
        return trim($record->number.' — '.($record->contact?->name ?: $record->contact?->email), ' —');
    }

    /** e.g. "Annapurna Circuit for 4 · USD 5,200 · Sent · valid until 1 Dec 2026" */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Quote $record */
        return GlobalSearch::line([
            $record->title,
            GlobalSearch::money($record->total, $record->currency),
            $record->status?->getLabel(),
            $record->valid_until ? 'valid until '.$record->valid_until->format('j M Y') : null,
        ]);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('contact')->latest();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotes::route('/'),
            'create' => CreateQuote::route('/create'),
            'view' => ViewQuote::route('/{record}'),
            'edit' => EditQuote::route('/{record}/edit'),
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
