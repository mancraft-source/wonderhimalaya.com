<?php

namespace App\Filament\Resources\TeamMembers\Schemas;

use App\Filament\Support\ContentFields;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TeamMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Profile')
                        ->columns(2)
                        ->schema([
                            ContentFields::title('name'),
                            ContentFields::slug(),
                            TextInput::make('role')
                                ->placeholder('e.g. Senior trekking guide')
                                ->maxLength(255),
                            TextInput::make('experience_years')
                                ->label('Years of experience')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(80),
                            Textarea::make('excerpt')
                                ->rows(2)
                                ->columnSpanFull(),
                            RichEditor::make('bio')
                                ->columnSpanFull(),
                        ]),
                    Section::make('Profile highlights & guest stories')
                        ->collapsible()
                        ->schema([
                            TagsInput::make('highlights')
                                ->placeholder('e.g. Served 1,000+ clients')
                                ->helperText('Short facts shown as badges on the profile (4 works best).'),
                            Textarea::make('praise')
                                ->label('What people say (intro)')
                                ->rows(3),
                            Repeater::make('quotes')
                                ->label('Guest quotes')
                                ->simple(Textarea::make('quote')->rows(3)->required())
                                ->defaultItems(0)
                                ->addActionLabel('Add quote'),
                        ]),
                    Section::make('Credentials')
                        ->columns(2)
                        ->schema([
                            TagsInput::make('languages')
                                ->placeholder('Add a language')
                                ->suggestions(['English', 'Nepali', 'Hindi', 'French', 'German', 'Spanish', 'Chinese', 'Japanese', 'Tibetan', 'Sherpa']),
                            TagsInput::make('certifications')
                                ->placeholder('Add a certification'),
                            KeyValue::make('social')
                                ->keyLabel('Network')
                                ->valueLabel('URL')
                                ->addActionLabel('Add link')
                                ->columnSpanFull(),
                        ]),
                    ContentFields::seoSection(),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Photo')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('photo')
                                ->collection('photo')
                                ->hiddenLabel()
                                ->image()
                                ->avatar()
                                ->imageEditor()
                                ->alignCenter(),
                        ]),
                    Section::make('Visibility')
                        ->schema([
                            Toggle::make('is_published')
                                ->label('Published')
                                ->default(true),
                            Toggle::make('is_guide')
                                ->label('Is a guide')
                                ->helperText('Guides appear in "Meet our guides" sections.'),
                            TextInput::make('sort')
                                ->numeric()
                                ->default(0)
                                ->required(),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }
}
