<?php

namespace App\Filament\Resources\Contacts\Tables;

use App\Filament\Support\CrmFields;
use App\Models\Contact;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
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
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
