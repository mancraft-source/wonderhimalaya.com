<?php

namespace App\Filament\Resources\Leads\Schemas;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Filament\Resources\Trips\Schemas\TripForm;
use App\Filament\Support\CrmFields;
use App\Models\Tier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class LeadForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()->columnSpan(2)->schema([
                    Section::make('Enquiry')->columns(2)->schema([
                        CrmFields::contactSelect()->columnSpanFull(),
                        Select::make('type')->options(LeadType::class)->default(LeadType::TailorMade)->required(),
                        Select::make('company_id')->label('Partner company')->relationship('company', 'name')->searchable()->preload(),
                        Select::make('trip_id')
                            ->relationship('trip', 'title')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('trip_departure_id', null)),
                        Select::make('tier_id')->label('Tier')->options(fn () => Tier::query()->orderBy('sort')->pluck('name', 'id')),
                        CrmFields::departureSelect(),
                        TagsInput::make('destinations')->placeholder('Everest, Annapurna…'),
                        TagsInput::make('interests')->placeholder('Trekking, culture…')->columnSpanFull(),
                        Textarea::make('message')->rows(5)->columnSpanFull(),
                    ]),
                    Section::make('Travel details')->columns(3)->schema([
                        DatePicker::make('start_date')->native(false),
                        DatePicker::make('end_date')->native(false)->afterOrEqual('start_date'),
                        TextInput::make('travel_month')->placeholder('2027-04')->maxLength(20),
                        TextInput::make('date_flexibility')->maxLength(255),
                        TextInput::make('duration_days')->label('Duration (days)')->numeric()->minValue(1),
                        TextInput::make('budget')->maxLength(255),
                        TextInput::make('adults')->numeric()->default(1)->minValue(0)->required(),
                        TextInput::make('children')->numeric()->default(0)->minValue(0)->required(),
                    ]),
                    Section::make('Source & attribution')->columns(2)->collapsed()->schema([
                        TextInput::make('source')->maxLength(255),
                        TextInput::make('landing_page')->maxLength(1000),
                        TextInput::make('referrer')->maxLength(1000)->columnSpanFull(),
                        KeyValue::make('utm')->label('UTM parameters')->columnSpanFull(),
                    ]),
                ]),
                Group::make()->schema([
                    Section::make('Pipeline')->schema([
                        Select::make('status')
                            ->options(LeadStatus::class)
                            ->default(LeadStatus::New)
                            ->required()
                            ->live(),
                        TextInput::make('lost_reason')
                            ->maxLength(255)
                            ->visible(fn (Get $get): bool => self::isLost($get('status')))
                            ->required(fn (Get $get): bool => self::isLost($get('status'))),
                        CrmFields::ownerSelect(),
                        DateTimePicker::make('next_follow_up_at')->label('Next follow-up')->native(false)->seconds(false),
                        TextInput::make('estimated_value')->numeric()->minValue(0)->prefix('≈'),
                        Select::make('currency')->options(TripForm::CURRENCIES)->default('USD')->required(),
                    ]),
                ]),
            ]);
    }

    public static function isLost(mixed $status): bool
    {
        return $status === LeadStatus::Lost || $status === LeadStatus::Lost->value;
    }
}
