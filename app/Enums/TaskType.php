<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TaskType: string implements HasColor, HasLabel
{
    case Todo = 'todo';
    case Call = 'call';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case Meeting = 'meeting';

    public function getLabel(): string
    {
        return match ($this) {
            self::Todo => 'To-do',
            self::Call => 'Call',
            self::Email => 'Email',
            self::WhatsApp => 'WhatsApp',
            self::Meeting => 'Meeting',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Todo => 'gray',
            self::Call => 'primary',
            self::Email => 'info',
            self::WhatsApp => 'success',
            self::Meeting => 'warning',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }
}
