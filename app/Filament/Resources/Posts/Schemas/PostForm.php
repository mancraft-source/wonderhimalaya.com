<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Enums\PublishStatus;
use App\Filament\Support\ContentFields;
use App\Filament\Support\SeoFields;
use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make([
                    Section::make('Post')
                        ->columns(2)
                        ->schema([
                            ContentFields::title()->columnSpanFull(),
                            ContentFields::slug(),
                            Textarea::make('excerpt')
                                ->rows(3)
                                ->columnSpanFull(),
                            RichEditor::make('body')
                                ->columnSpanFull()
                                ->fileAttachmentsDisk('public')
                                ->fileAttachmentsDirectory('posts')
                                ->fileAttachmentsVisibility('public'),
                        ]),
                    SeoFields::section(Post::class),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Publishing')
                        ->schema([
                            Select::make('status')
                                ->options(PublishStatus::class)
                                ->default(PublishStatus::Draft)
                                ->required(),
                            DateTimePicker::make('published_at')
                                ->label('Publish date')
                                ->default(now()),
                            Toggle::make('is_featured')
                                ->label('Featured'),
                        ]),
                    Section::make('Organisation')
                        ->schema([
                            Select::make('post_category_id')
                                ->label('Category')
                                ->relationship('category', 'name')
                                ->searchable()
                                ->preload()
                                ->createOptionForm(self::nameSlugFields()),
                            Select::make('team_member_id')
                                ->label('Author')
                                ->relationship('author', 'name')
                                ->searchable()
                                ->preload(),
                            Select::make('tags')
                                ->relationship('tags', 'name')
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->createOptionForm(self::nameSlugFields('tags')),
                        ]),
                    Section::make('Hero image')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('hero')
                                ->collection('hero')
                                ->hiddenLabel()
                                ->image()
                                ->imageEditor(),
                        ]),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    /** @return array<int, TextInput> */
    protected static function nameSlugFields(?string $table = null): array
    {
        $slug = ContentFields::slug(unique: false);

        return [
            ContentFields::title('name'),
            $table ? $slug->unique($table, 'slug') : $slug->unique('post_categories', 'slug'),
        ];
    }
}
