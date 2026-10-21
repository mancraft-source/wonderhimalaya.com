<?php

namespace App\Filament\Resources\Trips\RelationManagers;

use App\Filament\Resources\Testimonials\Schemas\TestimonialForm;
use App\Filament\Resources\Testimonials\Tables\TestimonialsTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/** Reviews of this trip (shown on the trip page). */
class TestimonialsRelationManager extends RelationManager
{
    protected static string $relationship = 'testimonials';

    protected static ?string $title = 'Reviews';

    protected static string|\BackedEnum|null $icon = Heroicon::OutlinedChatBubbleLeftRight;

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return TestimonialForm::configure($schema, withTrip: false);
    }

    public function table(Table $table): Table
    {
        return TestimonialsTable::configure($table, withTrip: false)
            ->recordTitleAttribute('author_name')
            ->headerActions([
                CreateAction::make()->label('Add review'),
            ]);
    }
}
