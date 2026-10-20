<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Actions\Contacts\AnonymiseContact;
use App\Actions\Contacts\ContactDataExport;
use App\Filament\Resources\Contacts\ContactResource;
use App\Filament\Support\CrmFields;
use App\Models\Contact;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditContact extends EditRecord
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->name ?: 'Contact';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')
                ->label('WhatsApp')
                ->icon(Heroicon::OutlinedChatBubbleOvalLeftEllipsis)
                ->color('success')
                ->visible(fn (Contact $record): bool => filled(CrmFields::whatsappUrl($record->whatsapp ?: $record->phone)))
                ->url(fn (Contact $record): ?string => CrmFields::whatsappUrl($record->whatsapp ?: $record->phone))
                ->openUrlInNewTab(),
            Action::make('email')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->url(fn (Contact $record): string => 'mailto:'.$record->email),
            ActionGroup::make([
                Action::make('export_data')
                    ->label('Export data (GDPR)')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(function (Contact $record) {
                        $json = json_encode(app(ContactDataExport::class)->handle($record), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                        return response()->streamDownload(fn () => print ($json), 'contact-'.$record->id.'-data.json', ['Content-Type' => 'application/json']);
                    }),
                Action::make('anonymise')
                    ->label('Anonymise (GDPR erasure)')
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->color('danger')
                    ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
                    ->requiresConfirmation()
                    ->modalDescription('Personal data on this contact, their enquiries and travellers will be permanently blanked. Bookings and payments are kept for accounting. This cannot be undone.')
                    ->action(function (Contact $record): void {
                        abort_unless(auth()->user()?->isAdmin(), 403);
                        app(AnonymiseContact::class)->handle($record);
                        $this->refreshFormData(array_keys($record->getAttributes()));
                        Notification::make()->success()->title('Contact anonymised')->send();
                    }),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ]),
        ];
    }
}
