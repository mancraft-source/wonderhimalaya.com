<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Resources\Trips\TripResource;
use App\Models\Lead;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class LeadInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()->columnSpan(2)->schema([
                    Section::make('Trip')
                        ->icon(Heroicon::OutlinedMap)
                        ->columns(3)
                        ->schema([
                            TextEntry::make('type')->badge(),
                            TextEntry::make('trip.title')
                                ->label('Trip')
                                ->placeholder('Tailor-made')
                                ->url(fn (Lead $record): ?string => $record->trip ? TripResource::getUrl('edit', ['record' => $record->trip]) : null)
                                ->columnSpan(2),
                            TextEntry::make('tier.name')->label('Tier')->placeholder('—'),
                            TextEntry::make('departure.start_date')->label('Departure')->date('D j M Y')->placeholder('—'),
                            TextEntry::make('destinations')->badge()->placeholder('—'),
                            TextEntry::make('interests')->badge()->placeholder('—')->columnSpanFull(),
                        ]),
                    Section::make('Travel details')
                        ->icon(Heroicon::OutlinedCalendarDays)
                        ->columns(4)
                        ->schema([
                            TextEntry::make('start_date')->date('j M Y')->placeholder('—'),
                            TextEntry::make('end_date')->date('j M Y')->placeholder('—'),
                            TextEntry::make('travel_month')->placeholder('—'),
                            TextEntry::make('date_flexibility')->label('Flexibility')->placeholder('—'),
                            TextEntry::make('duration_days')->label('Days')->placeholder('—'),
                            TextEntry::make('adults'),
                            TextEntry::make('children'),
                            TextEntry::make('budget')->placeholder('—'),
                        ]),
                    Section::make('Message')
                        ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
                        ->schema([
                            TextEntry::make('message')->hiddenLabel()->placeholder('No message')->prose()
                                ->formatStateUsing(fn (?string $state): string => nl2br(e((string) $state)))
                                ->html(),
                        ]),
                    Section::make('Source & attribution')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->collapsed()
                        ->columns(2)
                        ->schema([
                            TextEntry::make('source')->placeholder('—'),
                            TextEntry::make('landing_page')->placeholder('—')->limit(80),
                            TextEntry::make('referrer')->placeholder('—')->limit(80),
                            TextEntry::make('ip_address')->label('IP')->placeholder('—'),
                            KeyValueEntry::make('utm')->label('UTM')->columnSpanFull(),
                        ]),
                ]),
                Group::make()->schema([
                    Section::make('Contact')
                        ->icon(Heroicon::OutlinedUser)
                        ->schema([
                            TextEntry::make('contact.name')
                                ->label('Name')
                                ->weight('bold')
                                ->url(fn (Lead $record): ?string => $record->contact ? ContactResource::getUrl('edit', ['record' => $record->contact]) : null),
                            TextEntry::make('contact.email')->label('Email')->copyable()->icon(Heroicon::OutlinedEnvelope),
                            TextEntry::make('contact.phone')->label('Phone')->copyable()->placeholder('—')->icon(Heroicon::OutlinedPhone),
                            TextEntry::make('contact.country')->label('Country')->placeholder('—'),
                            TextEntry::make('company.name')->label('Company')->placeholder('—'),
                        ]),
                    Section::make('Pipeline')
                        ->icon(Heroicon::OutlinedFunnel)
                        ->schema([
                            Grid::make(2)->schema([
                                TextEntry::make('reference')->copyable(),
                                TextEntry::make('status')->badge(),
                            ]),
                            TextEntry::make('lost_reason')->visible(fn (Lead $record): bool => filled($record->lost_reason)),
                            TextEntry::make('owner.name')->label('Owner')->placeholder('Unassigned'),
                            TextEntry::make('estimated_value')->money(fn (Lead $record): string => $record->currency ?: 'USD')->placeholder('—'),
                            TextEntry::make('next_follow_up_at')
                                ->label('Next follow-up')
                                ->dateTime('D j M Y, H:i')
                                ->placeholder('—')
                                ->color(fn (Lead $record): ?string => $record->next_follow_up_at?->isPast() ? 'danger' : null),
                            TextEntry::make('created_at')->label('Received')->dateTime('j M Y, H:i')->since(),
                        ]),
                ]),
            ]);
    }
}
