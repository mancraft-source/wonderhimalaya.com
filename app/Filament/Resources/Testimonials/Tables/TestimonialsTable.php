<?php

namespace App\Filament\Resources\Testimonials\Tables;

use App\Filament\Resources\Testimonials\Schemas\TestimonialForm;
use App\Filament\Support\TableEmptyState;
use App\Models\Testimonial;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class TestimonialsTable
{
    public static function configure(Table $table, bool $withTrip = true): Table
    {
        return $table
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistColumnSearchesInSession()
            ->emptyStateIcon(TableEmptyState::icon($table, Heroicon::OutlinedChatBubbleLeftRight))
            ->emptyStateHeading(TableEmptyState::heading($table, $withTrip ? 'No reviews yet' : 'No reviews for this trip yet'))
            ->emptyStateDescription(TableEmptyState::description($table, $withTrip
                ? 'Reviews travellers submit on trip pages arrive here for approval, and you can add your own.'
                : 'Reviews travellers submit on this trip\'s page arrive here for approval.'))
            ->emptyStateActions($withTrip ? [CreateAction::make()] : [])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('trip'))
            ->defaultSort('reviewed_at', 'desc')
            ->columns([
                TextColumn::make('author_name')
                    ->label('Author')
                    ->searchable(['author_name', 'title', 'body'])
                    ->sortable()
                    ->description(fn (Testimonial $record): ?string => $record->author_country),
                TextColumn::make('rating')
                    ->formatStateUsing(fn (?int $state): string => TestimonialForm::ratingOptions()[$state] ?? '')
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => TestimonialForm::SOURCES[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'google' => 'info',
                        'tripadvisor' => 'success',
                        'website' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('trip.title')
                    ->label('Trip')
                    ->limit(40)
                    ->visible($withTrip)
                    ->toggleable(),
                ToggleColumn::make('is_published')
                    ->label('Published'),
                ToggleColumn::make('is_featured')
                    ->label('Featured'),
                TextColumn::make('reviewed_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')->options(TestimonialForm::SOURCES),
                SelectFilter::make('rating')->options(TestimonialForm::ratingOptions()),
                TernaryFilter::make('is_featured')->label('Featured'),
                TernaryFilter::make('is_published')
                    ->label('Published')
                    ->trueLabel('Published')
                    ->falseLabel('Pending approval'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approve')
                        ->label('Approve (publish)')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->icon(Heroicon::OutlinedEyeSlash)
                        ->color('gray')
                        ->action(fn (Collection $records) => $records->each->update(['is_published' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
