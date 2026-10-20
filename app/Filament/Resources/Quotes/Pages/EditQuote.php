<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteActions;
use App\Filament\Resources\Quotes\QuoteResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    public function getTitle(): string
    {
        return 'Quote '.$this->getRecord()->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            QuoteActions::markAsSent(),
            QuoteActions::preview(),
            QuoteActions::downloadPdf(),
            ActionGroup::make([
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ]),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['subtotal'], $data['total'], $data['deposit_amount'], $data['number']);

        return $data;
    }

    protected function afterSave(): void
    {
        QuoteResource::recalculate($this->getRecord());
        $this->refreshFormData(['subtotal', 'total', 'deposit_amount']);
    }
}
