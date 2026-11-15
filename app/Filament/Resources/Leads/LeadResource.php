<?php

namespace App\Filament\Resources\Leads;

use App\Enums\LeadStatus;
use App\Filament\RelationManagers\NotesRelationManager;
use App\Filament\RelationManagers\TasksRelationManager;
use App\Filament\Resources\Leads\Pages\CreateLead;
use App\Filament\Resources\Leads\Pages\EditLead;
use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Filament\Resources\Leads\Pages\ViewLead;
use App\Filament\Resources\Leads\RelationManagers\QuotesRelationManager;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Leads\Schemas\LeadInfolist;
use App\Filament\Resources\Leads\Tables\LeadsTable;
use App\Filament\Support\GlobalSearch;
use App\Filament\Support\SearchesPhoneNumbers;
use App\Models\Lead;
use App\Models\User;
use App\Support\Permissions;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class LeadResource extends Resource
{
    use SearchesPhoneNumbers;

    protected static ?string $model = Lead::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    protected static int $globalSearchResultsLimit = GlobalSearch::RESULTS_LIMIT;

    public static function form(Schema $schema): Schema
    {
        return LeadForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LeadInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeadsTable::configure($table);
    }

    /**
     * Sales staff without crm.view_all only see unassigned leads and their own.
     */
    public static function getEloquentQuery(): Builder
    {
        return static::scopeVisibleTo(parent::getEloquentQuery(), auth()->user());
    }

    public static function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->can(Permissions::CRM_VIEW_ALL)) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->whereNull('owner_id')->orWhere('owner_id', $user->id));
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', LeadStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'New leads';
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'contact.email', 'contact.first_name', 'contact.last_name', 'contact.phone', 'contact.whatsapp'];
    }

    /** @return array<int, string> */
    protected static function getGlobalSearchPhoneColumns(): array
    {
        return ['contact.phone', 'contact.whatsapp'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var Lead $record */
        return trim($record->reference.' — '.($record->contact?->name ?: $record->contact?->email), ' —');
    }

    /** e.g. "Tailor-made · Everest Base Camp Trek · New · 2 days ago" */
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Lead $record */
        return GlobalSearch::line([
            $record->type?->getLabel(),
            $record->trip?->title,
            $record->status?->getLabel(),
            $record->created_at?->diffForHumans(),
        ]);
    }

    public static function getGlobalSearchResultUrl(Model $record): string
    {
        return static::getUrl('view', ['record' => $record]);
    }

    /** Scoped like the list: sales staff only find their own and unassigned leads. */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['contact', 'trip'])->latest();
    }

    public static function getRelations(): array
    {
        return [
            NotesRelationManager::class,
            TasksRelationManager::class,
            QuotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'view' => ViewLead::route('/{record}'),
            'edit' => EditLead::route('/{record}/edit'),
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
