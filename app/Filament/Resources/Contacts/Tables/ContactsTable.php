<?php

namespace App\Filament\Resources\Contacts\Tables;

use App\Filament\Exports\ContactExporter;
use App\Filament\Support\CrmFields;
use App\Filament\Support\ExportActions;
use App\Filament\Support\TableEmptyState;
use App\Models\Contact;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedUsers))
            ->emptyStateHeading(TableEmptyState::heading($table, 'No contacts yet'))
            ->emptyStateDescription(TableEmptyState::description($table, 'Everyone who sends an enquiry or signs up for the newsletter is added here automatically.'))
            ->emptyStateActions([CreateAction::make()])
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['company', 'owner'])->withCount(['leads', 'bookings']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('first_name')
                    ->label('Name')
                    ->formatStateUsing(fn (Contact $record): string => $record->name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name'])
                    ->weight('medium'),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('phone')->searchable()->toggleable(),
                TextColumn::make('country')->sortable()->toggleable(),
                TextColumn::make('company.name')->label('Company')->toggleable(),
                TextColumn::make('leads_count')->label('Leads')->sortable(),
                TextColumn::make('bookings_count')->label('Bookings')->sortable(),
                IconColumn::make('marketing_consent')->label('Consent')->boolean()->toggleable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->sortable(),
            ])
            ->filters([
                TernaryFilter::make('marketing_consent')->label('Marketing consent'),
                SelectFilter::make('company_id')->label('Company')->relationship('company', 'name')->searchable(),
                SelectFilter::make('owner_id')->label('Owner')->options(fn () => CrmFields::staffOptions()),
                TrashedFilter::make(),
            ])
            ->headerActions([
                ExportActions::header(ContactExporter::class),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportActions::bulk(ContactExporter::class),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
