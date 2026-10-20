<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Quote;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['payable', 'contact']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('uuid')
                    ->label('ID')
                    ->formatStateUsing(fn (string $state): string => substr($state, 0, 8))
                    ->searchable()
                    ->copyable()
                    ->copyableState(fn (Payment $record): string => $record->uuid)
                    ->fontFamily('mono'),
                TextColumn::make('payable_type')
                    ->label('For')
                    ->state(fn (Payment $record): string => self::payableLabel($record))
                    ->url(fn (Payment $record): ?string => self::payableUrl($record)),
                TextColumn::make('contact.email')
                    ->label('Contact')
                    ->formatStateUsing(fn (Payment $record): string => $record->contact?->name ?: (string) $record->contact?->email)
                    ->searchable(['first_name', 'last_name', 'email'])
                    ->placeholder('—'),
                TextColumn::make('gateway')->badge()->color('gray')->placeholder('Any'),
                TextColumn::make('type')->badge(),
                TextColumn::make('amount')->money(fn (Payment $record): string => $record->currency ?: 'USD')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('paid_at')->dateTime('j M Y, H:i')->sortable()->placeholder('—'),
                TextColumn::make('expires_at')->dateTime('j M Y')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gateway_reference')->label('Reference')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->since()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(PaymentStatus::class)->multiple(),
                SelectFilter::make('type')->options(PaymentType::class),
                SelectFilter::make('gateway')->options(['paypal' => 'PayPal', 'nepal_bank' => 'Nepal bank', 'manual' => 'Manual']),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('open_link')
                        ->label('Open payment link')
                        ->icon(Heroicon::OutlinedLink)
                        ->url(fn (Payment $record): string => PaymentResource::payUrl($record))
                        ->openUrlInNewTab()
                        ->visible(fn (Payment $record): bool => $record->isPayable()),
                    self::markAsPaidAction(),
                    Action::make('cancel')
                        ->label('Cancel link')
                        ->icon(Heroicon::OutlinedXMark)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::Pending)
                        ->authorize(fn (): bool => (bool) auth()->user()?->can(Permissions::PAYMENTS))
                        ->action(fn (Payment $record) => $record->update(['status' => PaymentStatus::Cancelled])),
                ]),
            ]);
    }

    /** Records an offline payment (bank transfer, cash) and updates the booking totals. */
    public static function markAsPaidAction(): Action
    {
        return Action::make('mark_as_paid')
            ->label('Mark as paid manually')
            ->icon(Heroicon::OutlinedBanknotes)
            ->color('success')
            ->visible(fn (Payment $record): bool => in_array($record->status, [PaymentStatus::Pending, PaymentStatus::Failed, PaymentStatus::Cancelled], true))
            ->authorize(fn (): bool => (bool) auth()->user()?->can(Permissions::PAYMENTS))
            ->modalDescription('Use for bank transfers or cash received outside the online gateways.')
            ->fillForm(fn (Payment $record): array => ['amount' => $record->amount, 'paid_at' => now(), 'method' => 'bank_transfer'])
            ->schema([
                TextInput::make('amount')->numeric()->required()->minValue(0.01),
                Select::make('method')->options(['bank_transfer' => 'Bank transfer', 'cash' => 'Cash', 'card_terminal' => 'Card terminal (office)', 'other' => 'Other'])->required(),
                TextInput::make('reference')->label('Reference / receipt no.')->maxLength(255),
                DateTimePicker::make('paid_at')->native(false)->required(),
            ])
            ->action(function (Payment $record, array $data): void {
                DB::transaction(function () use ($record, $data): void {
                    $record->update([
                        'status' => PaymentStatus::Paid,
                        'amount' => round((float) $data['amount'], 2),
                        'paid_at' => $data['paid_at'] ?? now(),
                        'gateway' => 'manual',
                        'gateway_reference' => $data['reference'] ?? null,
                        'gateway_payload' => [
                            'method' => $data['method'] ?? null,
                            'recorded_by' => auth()->id(),
                            'recorded_at' => now()->toIso8601String(),
                        ],
                    ]);

                    $payable = $record->payable;

                    if ($payable && method_exists($payable, 'syncPaymentTotals')) {
                        $payable->syncPaymentTotals();
                    }
                });

                Notification::make()->success()->title('Payment recorded')->send();
            });
    }

    public static function payableLabel(Payment $payment): string
    {
        return match (true) {
            $payment->payable instanceof Booking => 'Booking '.$payment->payable->number,
            $payment->payable instanceof Quote => 'Quote '.$payment->payable->number,
            default => ucfirst((string) $payment->payable_type).' #'.$payment->payable_id,
        };
    }

    public static function payableUrl(Payment $payment): ?string
    {
        return match (true) {
            $payment->payable instanceof Booking => BookingResource::getUrl('edit', ['record' => $payment->payable]),
            $payment->payable instanceof Quote => QuoteResource::getUrl('edit', ['record' => $payment->payable]),
            default => null,
        };
    }
}
