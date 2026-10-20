<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Actions\Bookings\CreatePaymentLink;
use App\Enums\PaymentType;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\Permissions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    public function getTitle(): string
    {
        return 'Booking '.$this->getRecord()->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->createPaymentLinkAction(),
            ActionGroup::make([
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ]),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // amount_paid is derived from payments only.
        unset($data['amount_paid'], $data['number']);

        return $data;
    }

    public function createPaymentLinkAction(): Action
    {
        $creator = app(CreatePaymentLink::class);

        return Action::make('create_payment_link')
            ->label('Create payment link')
            ->icon(Heroicon::OutlinedLink)
            ->authorize(fn (): bool => (bool) auth()->user()?->can(Permissions::PAYMENTS))
            ->fillForm(fn (Booking $record): array => [
                'type' => $record->amount_paid > 0 ? PaymentType::Balance->value : PaymentType::Deposit->value,
                'amount' => $creator->suggestedAmount($record, $record->amount_paid > 0 ? PaymentType::Balance : PaymentType::Deposit),
                'gateway' => 'any',
                'expires_in_days' => 7,
            ])
            ->schema([
                Select::make('type')
                    ->options(PaymentType::class)
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, mixed $state) use ($creator): void {
                        $type = $state instanceof PaymentType ? $state : PaymentType::tryFrom((string) $state);

                        if ($type && $type !== PaymentType::Custom) {
                            $set('amount', $creator->suggestedAmount($this->getRecord(), $type));
                        }
                    }),
                TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->prefix(fn (): string => $this->getRecord()->currency ?: 'USD'),
                Select::make('gateway')->options(CreatePaymentLink::GATEWAYS)->default('any')->required(),
                TextInput::make('expires_in_days')->label('Expires in (days)')->numeric()->integer()->minValue(1)->maxValue(90)->default(7),
                TextInput::make('description')->maxLength(255)->placeholder(fn (Get $get): string => 'Payment for booking '.$this->getRecord()->number),
            ])
            ->action(function (Booking $record, array $data, Action $action) use ($creator): void {
                $type = $data['type'] instanceof PaymentType ? $data['type'] : PaymentType::from($data['type']);

                $payment = $creator->handle(
                    $record,
                    $type,
                    (float) $data['amount'],
                    $data['gateway'] ?? null,
                    isset($data['expires_in_days']) ? (int) $data['expires_in_days'] : null,
                    auth()->user(),
                    $data['description'] ?? null,
                );

                $this->replaceMountedAction('paymentLink', ['payment' => $payment->uuid]);
            });
    }

    /** Modal shown after a link is created so staff can copy it. */
    public function paymentLinkAction(): Action
    {
        return Action::make('paymentLink')
            ->modalHeading('Payment link created')
            ->modalDescription('Send this link to the client. It can be paid once.')
            ->fillForm(function (array $arguments): array {
                $payment = Payment::where('uuid', $arguments['payment'] ?? null)->first();

                return ['url' => $payment ? PaymentResource::payUrl($payment) : null];
            })
            ->schema([
                TextInput::make('url')->label('Link')->readOnly()->copyable(),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Done');
    }
}
