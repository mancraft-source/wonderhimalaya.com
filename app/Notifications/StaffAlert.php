<?php

namespace App\Notifications;

use App\Support\Notifications\NotificationSettings;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * A staff alert (new lead, payment, booking, assignment): email and/or the admin bell, as configured
 * under Settings → Notifications. Extra addresses (anonymous notifiables) always get email.
 */
class StaffAlert extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, string>  $lines  Markdown lines for the email; the first one doubles as the bell text
     */
    public function __construct(
        public string $event,
        public string $subject,
        public array $lines,
        public string $actionLabel,
        public string $actionUrl,
        public ?string $replyToEmail = null,
        public ?string $replyToName = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : NotificationSettings::channels($this->event);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->subject);

        foreach ($this->lines as $line) {
            $mail->line($line);
        }

        if ($this->replyToEmail) {
            $mail->replyTo($this->replyToEmail, $this->replyToName);
        }

        return $mail->action($this->actionLabel, $this->actionUrl);
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return FilamentNotification::make()
            ->title($this->subject)
            ->body(strip_tags(str((string) ($this->lines[0] ?? ''))->markdown()->toString()))
            ->icon(match ($this->event) {
                'new_lead' => 'heroicon-o-inbox-arrow-down',
                'payment_received_staff' => 'heroicon-o-banknotes',
                'booking_confirmed_staff' => 'heroicon-o-check-badge',
                default => 'heroicon-o-user-plus',
            })
            ->iconColor(match ($this->event) {
                'payment_received_staff', 'booking_confirmed_staff' => 'success',
                default => 'primary',
            })
            ->actions([Action::make('open')->label($this->actionLabel)->url($this->actionUrl)->markAsRead()])
            ->getDatabaseMessage();
    }
}
