<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Enums\PublishStatus;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Models\Page;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('parent'))
            ->defaultSort('path')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Page $record): string => $record->publicPath()),
                TextColumn::make('path')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('parent.title')
                    ->label('Parent')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('template')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PageForm::TEMPLATES[$state] ?? (string) $state)
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                IconColumn::make('show_in_footer')
                    ->label('Footer')
                    ->boolean(),
                TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PublishStatus::class),
                SelectFilter::make('template')->options(PageForm::TEMPLATES),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('view_on_site')
                        ->label('View on site')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url(fn (Page $record): string => url($record->publicPath()))
                        ->openUrlInNewTab()
                        ->hidden(fn (Page $record): bool => $record->trashed()),
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
