<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Leads\LeadResource;
use App\Models\Lead;
use App\Support\Permissions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** The latest enquiries the user may see, newest first, each linking to the lead. */
class RecentEnquiries extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->can(Permissions::for('leads', Permissions::VIEW));
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent enquiries')
            ->query(fn () => LeadResource::scopeVisibleTo(Lead::query(), auth()->user())->with(['contact', 'trip']))
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Lead $record): string => LeadResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('No enquiries yet')
            ->emptyStateDescription('New enquiries from the website will appear here as they arrive.')
            ->columns([
                TextColumn::make('reference')->weight('medium'),
                TextColumn::make('contact.name')
                    ->label('Name')
                    ->state(fn (Lead $record): ?string => $record->contact?->name)
                    ->placeholder('—'),
                TextColumn::make('type')->badge(),
                TextColumn::make('trip.title')->label('Trip')->limit(40)->placeholder('—'),
                TextColumn::make('source')
                    ->formatStateUsing(fn (?string $state): string => ucfirst((string) $state))
                    ->placeholder('Unknown'),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->since()
                    ->dateTimeTooltip('D j M Y, H:i', timezone: 'Asia/Kathmandu')
                    ->sortable(),
                TextColumn::make('status')->badge(),
            ]);
    }
}
