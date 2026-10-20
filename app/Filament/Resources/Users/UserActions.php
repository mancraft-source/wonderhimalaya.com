<?php

namespace App\Filament\Resources\Users;

use App\Models\User;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** Sanctum personal access token management for API integrations. */
final class UserActions
{
    public const ABILITIES = [
        'read' => 'read — catalogue & content',
        'leads:write' => 'leads:write — submit enquiries',
        'bookings:read' => 'bookings:read',
        'bookings:write' => 'bookings:write',
    ];

    public static function canManageTokens(User $record): bool
    {
        $user = auth()->user();

        return $user && $user->can(Permissions::API) && ($record->is($user) || $user->can(Permissions::USERS));
    }

    /** Creates a token; the page shows the plaintext once via the `apiTokenCreated` action. */
    public static function createToken(): Action
    {
        return Action::make('create_api_token')
            ->label('New API token')
            ->icon(Heroicon::OutlinedKey)
            ->color('gray')
            ->visible(fn (User $record): bool => self::canManageTokens($record))
            ->authorize(fn (User $record): bool => self::canManageTokens($record))
            ->schema([
                TextInput::make('name')->label('Token name')->required()->maxLength(100)->placeholder('e.g. Website, Zapier'),
                CheckboxList::make('abilities')->options(self::ABILITIES)->required()->default(['read']),
                Select::make('expires_in')
                    ->label('Expires')
                    ->options(['30' => 'In 30 days', '90' => 'In 90 days', '365' => 'In 1 year', 'never' => 'Never'])
                    ->default('365')
                    ->required(),
            ])
            ->action(function (User $record, array $data, $livewire): void {
                $abilities = array_values(array_intersect($data['abilities'] ?? [], array_keys(self::ABILITIES)));
                $expiresAt = ($data['expires_in'] ?? 'never') === 'never' ? null : now()->addDays((int) $data['expires_in']);

                $token = $record->createToken($data['name'], $abilities, $expiresAt);

                $livewire->replaceMountedAction('apiTokenCreated', ['token' => $token->plainTextToken]);
            });
    }

    public static function tokenCreated(): Action
    {
        return Action::make('apiTokenCreated')
            ->modalHeading('API token created')
            ->modalDescription('Copy this token now — it will not be shown again.')
            ->fillForm(fn (array $arguments): array => ['token' => $arguments['token'] ?? null])
            ->schema([
                TextInput::make('token')->hiddenLabel()->readOnly()->copyable(),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Done');
    }

    public static function revokeTokens(): Action
    {
        return Action::make('revoke_api_tokens')
            ->label('Revoke API tokens')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (User $record): bool => self::canManageTokens($record) && $record->tokens()->exists())
            ->authorize(fn (User $record): bool => self::canManageTokens($record))
            ->schema(fn (User $record): array => [
                CheckboxList::make('tokens')
                    ->options($record->tokens()->latest()->get()->mapWithKeys(fn ($token) => [
                        $token->id => $token->name.' ('.implode(', ', (array) $token->abilities).') — '
                            .($token->last_used_at ? 'last used '.$token->last_used_at->diffForHumans() : 'never used'),
                    ])->all())
                    ->required()
                    ->bulkToggleable(),
            ])
            ->action(function (User $record, array $data): void {
                $count = $record->tokens()->whereIn('id', $data['tokens'] ?? [])->delete();
                Notification::make()->success()->title($count.' token(s) revoked')->send();
            });
    }
}
