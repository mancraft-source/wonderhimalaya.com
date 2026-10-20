<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Enums\CompanyType;
use App\Filament\Resources\Companies\Schemas\CompanyForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('owner')->withCount(['contacts', 'leads', 'bookings']))
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('medium')->description(fn ($record) => $record->website),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => CompanyForm::STATUSES[$state] ?? $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('country')->sortable()->toggleable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('contacts_count')->label('Contacts')->sortable(),
                TextColumn::make('leads_count')->label('Leads')->sortable(),
                TextColumn::make('bookings_count')->label('Bookings')->sortable(),
                TextColumn::make('owner.name')->label('Owner')->placeholder('—')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')->options(CompanyType::class),
                SelectFilter::make('status')->options(CompanyForm::STATUSES),
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
