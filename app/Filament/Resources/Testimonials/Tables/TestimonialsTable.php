<?php

namespace App\Filament\Resources\Testimonials\Tables;

use App\Filament\Resources\Testimonials\Schemas\TestimonialForm;
use App\Models\Testimonial;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestimonialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                        default => 'gray',
                    }),
                TextColumn::make('trip.title')
                    ->label('Trip')
                    ->limit(40)
                    ->toggleable(),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),
                IconColumn::make('is_published')
                    ->label('Published')
                    ->boolean(),
                TextColumn::make('reviewed_at')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')->options(TestimonialForm::SOURCES),
                SelectFilter::make('rating')->options(TestimonialForm::ratingOptions()),
                TernaryFilter::make('is_featured')->label('Featured'),
                TernaryFilter::make('is_published')->label('Published'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
