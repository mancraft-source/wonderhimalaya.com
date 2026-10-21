<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Content;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Base for pages that edit one Setting key holding designed-template copy (see App\Support\Content).
 * The form is filled with the merged (saved + default) copy so editors always see what the site shows.
 *
 * @property-read Schema $form
 */
abstract class ContentSettingsPage extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Content';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    /** The settings row this page edits. */
    abstract protected static function settingKey(): string;

    /** @return array<string, mixed> Current copy (saved values merged over defaults). */
    abstract protected static function currentContent(): array;

    /** @return array<string, mixed> Launch defaults; only differences from these are stored. */
    abstract protected static function contentDefaults(): array;

    public static function canAccess(): bool
    {
        return auth()->user()?->can(Permissions::CONTENT) ?? false;
    }

    public function mount(): void
    {
        $this->form->fill(static::currentContent());
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save changes')
                            ->submit('save')
                            ->keyBindings(['mod+s']),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        Setting::put(static::settingKey(), Content::overrides($this->form->getState(), static::contentDefaults()));

        Notification::make()->title('Content saved')->success()->send();
    }

    protected function resetAction(): Action
    {
        return Action::make('resetToDefaults')
            ->label('Reset to defaults')
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('All edits on this page are discarded and the launch copy is restored. This cannot be undone.')
            ->action(function (): void {
                abort_unless(static::canAccess(), 403);

                Setting::query()->where('key', static::settingKey())->first()?->delete();

                $this->form->fill(static::currentContent());

                Notification::make()->title('Restored default content')->success()->send();
            });
    }
}
