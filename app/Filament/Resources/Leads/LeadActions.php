<?php

namespace App\Filament\Resources\Leads;

use App\Actions\Quotes\CreateQuoteFromLead;
use App\Enums\LeadStatus;
use App\Filament\Resources\Leads\Schemas\LeadForm;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Filament\Support\CrmFields;
use App\Models\Lead;
use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/** Header actions shared by the lead view and edit pages. */
final class LeadActions
{
    /** @return array<int, Action> */
    public static function all(): array
    {
        return [
            self::assignToMe(),
            self::changeStatus(),
            self::createQuote(),
            self::whatsapp(),
            self::email(),
        ];
    }

    public static function assignToMe(): Action
    {
        return Action::make('assign_to_me')
            ->label('Assign to me')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('gray')
            ->visible(fn (Lead $record): bool => $record->owner_id !== auth()->id())
            ->authorize(fn (Lead $record): bool => auth()->user()->can('update', $record))
            ->action(function (Lead $record): void {
                $record->update(['owner_id' => auth()->id()]);
                Notification::make()->success()->title('Lead assigned to you')->send();
            });
    }

    public static function changeStatus(): Action
    {
        return Action::make('change_status')
            ->label('Change status')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->color('gray')
            ->authorize(fn (Lead $record): bool => auth()->user()->can('update', $record))
            ->fillForm(fn (Lead $record): array => [
                'status' => $record->status,
                'lost_reason' => $record->lost_reason,
                'next_follow_up_at' => $record->next_follow_up_at,
            ])
            ->schema([
                Select::make('status')->options(LeadStatus::class)->required()->live(),
                TextInput::make('lost_reason')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => LeadForm::isLost($get('status')))
                    ->required(fn (Get $get): bool => LeadForm::isLost($get('status'))),
                DateTimePicker::make('next_follow_up_at')->label('Next follow-up')->native(false)->seconds(false),
            ])
            ->action(function (Lead $record, array $data): void {
                $status = $data['status'] instanceof LeadStatus ? $data['status'] : LeadStatus::from($data['status']);

                $record->update([
                    'status' => $status,
                    'lost_reason' => $status === LeadStatus::Lost ? ($data['lost_reason'] ?? null) : null,
                    'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
                    'converted_at' => in_array($status, [LeadStatus::DepositPaid, LeadStatus::Confirmed], true)
                        ? ($record->converted_at ?? now())
                        : $record->converted_at,
                ]);

                Notification::make()->success()->title('Status changed to '.$status->getLabel())->send();
            });
    }

    public static function createQuote(): Action
    {
        return Action::make('create_quote')
            ->label('Create quote')
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->requiresConfirmation()
            ->modalDescription('A draft quote will be created from this enquiry, priced from the trip or departure.')
            ->authorize(fn (): bool => auth()->user()->can('create', Quote::class))
            ->action(function (Lead $record) {
                $quote = app(CreateQuoteFromLead::class)->handle($record, auth()->user());

                Notification::make()->success()->title('Quote '.$quote->number.' created')->send();

                return redirect(QuoteResource::getUrl('edit', ['record' => $quote]));
            });
    }

    public static function whatsapp(): Action
    {
        return Action::make('whatsapp')
            ->label('WhatsApp')
            ->icon(Heroicon::OutlinedChatBubbleOvalLeftEllipsis)
            ->color('success')
            ->visible(fn (Lead $record): bool => filled(CrmFields::whatsappUrl($record->contact?->whatsapp ?: $record->contact?->phone)))
            ->url(fn (Lead $record): ?string => CrmFields::whatsappUrl($record->contact?->whatsapp ?: $record->contact?->phone))
            ->openUrlInNewTab();
    }

    public static function email(): Action
    {
        return Action::make('email')
            ->label('Email')
            ->icon(Heroicon::OutlinedEnvelope)
            ->color('gray')
            ->visible(fn (Lead $record): bool => filled($record->contact?->email))
            ->url(fn (Lead $record): string => 'mailto:'.$record->contact->email.'?subject='.rawurlencode('Your Wonder Himalaya enquiry '.$record->reference));
    }
}
