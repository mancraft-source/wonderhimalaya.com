<?php

namespace App\Filament\Resources\Quotes\Schemas;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Trips\Schemas\TripForm;
use App\Filament\Support\CrmFields;
use App\Models\Lead;
use App\Models\Tier;
use App\Support\QuoteDefaults;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class QuoteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()->columnSpan(2)->schema([
                    Section::make('Quote')->columns(2)->schema([
                        TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
                        Select::make('lead_id')
                            ->label('Lead')
                            ->relationship('lead', 'reference')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                if ($state && ($lead = Lead::find($state))) {
                                    $set('contact_id', $lead->contact_id);
                                    $set('trip_id', $lead->trip_id);
                                    $set('tier_id', $lead->tier_id);
                                    $set('pax', max(1, $lead->travellerCount()));
                                }
                            }),
                        CrmFields::contactSelect(),
                        Select::make('trip_id')
                            ->relationship('trip', 'title')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('trip_departure_id', null)),
                        Select::make('tier_id')->label('Tier')->options(fn () => Tier::query()->orderBy('sort')->pluck('name', 'id')),
                        CrmFields::departureSelect(),
                        TextInput::make('pax')->numeric()->minValue(1)->default(1)->required(),
                        DatePicker::make('start_date')->native(false),
                        DatePicker::make('end_date')->native(false)->afterOrEqual('start_date'),
                    ]),
                    Section::make('Line items')->schema([
                        Repeater::make('items')
                            ->hiddenLabel()
                            ->relationship('items')
                            ->orderColumn('sort')
                            ->defaultItems(1)
                            ->addActionLabel('Add line')
                            ->live(debounce: 500)
                            ->columns(6)
                            ->schema([
                                TextInput::make('description')->required()->maxLength(255)->columnSpan(3),
                                TextInput::make('quantity')->numeric()->default(1)->minValue(0)->required()->live(onBlur: true),
                                TextInput::make('unit_price')->label('Unit price')->numeric()->default(0)->required()->live(onBlur: true)->columnSpan(2),
                            ]),
                        TextEntry::make('totals_preview')
                            ->label('Totals preview')
                            ->state(fn (Get $get): string => self::previewTotals($get))
                            ->helperText('Final totals are recalculated on save.'),
                    ]),
                    Section::make('Introduction & terms')->collapsible()->schema([
                        RichEditor::make('introduction'),
                        RichEditor::make('terms')->default(fn () => QuoteDefaults::terms()),
                    ]),
                ]),
                Group::make()->schema([
                    Section::make('Status')->schema([
                        TextInput::make('number')->disabled()->dehydrated(false)->visibleOn('edit'),
                        Select::make('status')->options(QuoteStatus::class)->default(QuoteStatus::Draft)->required(),
                        DatePicker::make('valid_until')->native(false)->default(fn () => now()->addDays(14)),
                    ]),
                    Section::make('Pricing')->schema([
                        Select::make('currency')->options(TripForm::CURRENCIES)->default(fn () => QuoteDefaults::currency())->required()->live(),
                        TextInput::make('discount')->numeric()->minValue(0)->default(0)->live(onBlur: true),
                        TextInput::make('tax')->numeric()->minValue(0)->default(0)->live(onBlur: true),
                        TextInput::make('deposit_percent')
                            ->label('Deposit %')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->maxValue(100)
                            ->default(fn () => QuoteDefaults::depositPercent())
                            ->required()
                            ->live(onBlur: true),
                        TextEntry::make('saved_totals')
                            ->label('Saved totals')
                            ->visibleOn('edit')
                            ->state(fn ($record): ?string => $record
                                ? sprintf('Subtotal %s · Total %s · Deposit %s',
                                    number_format((float) $record->subtotal, 2),
                                    number_format((float) $record->total, 2),
                                    number_format((float) $record->deposit_amount, 2))
                                : null),
                    ]),
                ]),
            ]);
    }

    private static function previewTotals(Get $get): string
    {
        $subtotal = collect($get('items') ?? [])
            ->sum(fn ($item) => round((float) ($item['quantity'] ?? 0) * (float) ($item['unit_price'] ?? 0), 2));
        $total = max(0, $subtotal - (float) $get('discount') + (float) $get('tax'));
        $deposit = round($total * ((float) $get('deposit_percent') / 100), 2);
        $currency = $get('currency') ?: 'USD';

        return sprintf('Subtotal %1$s %2$s · Total %1$s %3$s · Deposit %1$s %4$s',
            $currency, number_format($subtotal, 2), number_format($total, 2), number_format($deposit, 2));
    }
}
