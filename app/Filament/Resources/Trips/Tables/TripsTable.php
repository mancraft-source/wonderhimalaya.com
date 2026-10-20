<?php

namespace App\Filament\Resources\Trips\Tables;

use App\Enums\PublishStatus;
use App\Filament\Resources\Trips\TripResource;
use App\Models\Trip;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TripsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['destination', 'difficulty', 'media']))
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                SpatieMediaLibraryImageColumn::make('hero')
                    ->label('')
                    ->collection('hero')
                    ->conversion('thumb')
                    ->imageSize(48)
                    ->square(),
                TextColumn::make('title')
                    ->searchable(['title', 'trip_code', 'slug'])
                    ->sortable()
                    ->description(fn (Trip $record): ?string => $record->trip_code)
                    ->wrap(),
                TextColumn::make('destination.name')
                    ->label('Destination')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('duration_days')
                    ->label('Duration')
                    ->formatStateUsing(fn (Trip $record): ?string => $record->durationLabel())
                    ->sortable(),
                TextColumn::make('price_from')
                    ->label('Price')
                    ->money(fn (Trip $record): string => $record->currency ?: 'USD')
                    ->description(fn (Trip $record): ?string => $record->sale_price !== null
                        ? 'Sale: '.number_format((float) $record->sale_price).' '.$record->currency
                        : null)
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_featured')->label('Featured')->boolean()->trueIcon(Heroicon::Star)->falseIcon(false)->toggleable(),
                IconColumn::make('is_popular')->label('Popular')->boolean()->trueIcon(Heroicon::Fire)->falseIcon(false)->toggleable(),
                IconColumn::make('is_top_selling')->label('Top')->boolean()->trueIcon(Heroicon::Trophy)->falseIcon(false)->toggleable(),
                IconColumn::make('is_fixed_departure')->label('Fixed dep.')->boolean()->trueIcon(Heroicon::CalendarDays)->falseIcon(false)->toggleable(),
                TextColumn::make('difficulty.name')->label('Difficulty')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->since()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(PublishStatus::class),
                SelectFilter::make('destination')
                    ->relationship('destinations', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('activity')
                    ->relationship('activities', 'name')
                    ->preload(),
                SelectFilter::make('difficulty_id')
                    ->label('Difficulty')
                    ->relationship('difficulty', 'name'),
                SelectFilter::make('tier')
                    ->relationship('tiers', 'name'),
                TernaryFilter::make('is_fixed_departure')->label('Fixed departure'),
                TernaryFilter::make('is_featured')->label('Featured'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                ActionGroup::make([
                    Action::make('view_on_site')
                        ->label('View on site')
                        ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                        ->url(fn (Trip $record): string => $record->url())
                        ->openUrlInNewTab(),
                    self::replicateAction(),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->icon(Heroicon::OutlinedEye)
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => PublishStatus::Published]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['status' => PublishStatus::Draft]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /** Duplicates a trip as a draft, including itinerary, FAQs, prices and taxonomy links. */
    public static function replicateAction(): ReplicateAction
    {
        return ReplicateAction::make()
            ->label('Duplicate')
            ->excludeAttributes(['trip_code', 'wp_id', 'wp_slug', 'published_at'])
            ->beforeReplicaSaved(function (Trip $replica, Trip $record): void {
                $replica->title = $record->title.' (copy)';
                $base = Str::slug($replica->title);
                $slug = $base;
                $i = 2;
                while (Trip::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $replica->slug = $slug;
                $replica->status = PublishStatus::Draft;
            })
            ->after(fn (Trip $replica, Trip $record) => self::copyRelations($record, $replica))
            ->successRedirectUrl(fn (Trip $replica): string => TripResource::getUrl('edit', ['record' => $replica]));
    }

    public static function copyRelations(Trip $from, Trip $to): void
    {
        foreach (['days', 'faqs', 'prices'] as $relation) {
            foreach ($from->{$relation}()->get() as $child) {
                $copy = $child->replicate();
                $copy->trip_id = $to->getKey();
                $copy->save();
            }
        }

        $to->destinations()->sync($from->destinations()->pluck('destinations.id'));
        $to->activities()->sync($from->activities()->pluck('activities.id'));
        $to->tiers()->sync($from->tiers()->pluck('tiers.id'));
        $to->tags()->sync($from->tags()->pluck('tags.id'));
    }
}
